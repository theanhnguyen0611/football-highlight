<?php
namespace App\Services;

use App\Models\FootballMatch;
use App\Models\MatchVideo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CrawlService
{
    private string $hoofoot = 'https://hoofoot.com';

    private string $sitemap = 'https://hoofoot.com/matchsitemap.php';

    // ─── Crawl sitemap → league pages → [slug => match_id] ──────
    public function crawlHoofootListings(): array
    {
        $result = [];

        $xml = $this->fetch($this->sitemap);
        if (!$xml) return $result;

        // 1. Match slugs trực tiếp trong sitemap
        $this->extractHoofootSlugs($xml, $result);

        // 2. League pages từ sitemap (dynamic, không hardcode)
        preg_match_all('/\?idp=(\d+)&(?:amp;)?([A-Za-z]+)/', $xml, $m);
        $leaguePages = array_combine($m[1], $m[2]);

        foreach ($leaguePages as $idp => $name) {
            $html = $this->fetch("{$this->hoofoot}/?idp={$idp}&{$name}");
            if ($html) $this->extractHoofootSlugs($html, $result);
            sleep(1);
        }

        return $result;
    }

    private function extractHoofootSlugs(?string $html, array &$result): void
    {
        if (!$html) return;
        preg_match_all('/\?match=([A-Za-z0-9_]+)/', $html, $slugMatches);
        preg_match_all('/recargar\(\'(\d+)\'\)/', $html, $idMatches);
        $slugs = $slugMatches[1] ?? [];
        $ids   = $idMatches[1]   ?? [];
        foreach ($slugs as $i => $slug) {
            if (!isset($result[$slug])) {
                $result[$slug] = $ids[$i] ?? null;
            }
        }
    }

    // ─── Lấy embed URL từ hoofoot match page ─────────────────────
    // Thử curl trước (hoofoot server-side renders iframe src),
    // chỉ fallback sang Playwright nếu cần vượt Cloudflare.
    public function getEmbedUrl(string $sourceUrl): ?string
    {
        $embedUrl = $this->getEmbedUrlViaCurl($sourceUrl)
            ?? $this->getEmbedUrlViaPlaywright($sourceUrl);

        return $embedUrl;
    }

    private function getEmbedUrlViaCurl(string $sourceUrl): ?string
    {
        $html = $this->fetch($sourceUrl);
        if (!$html) return null;

        $selectors = [
            '/iframe[^>]+src=["\']([^"\']*(?:videas|streamable|youtube|youtu\.be)[^"\']*)["\']/',
            '/<iframe[^>]+src=["\']([^"\']+)["\'][^>]*>/i',
        ];

        $blocked = ['dailymotion.com', 'facebook.com', 'twitter.com', 'tiktok.com'];

        foreach ($selectors as $pattern) {
            if (preg_match($pattern, $html, $m)) {
                $url = html_entity_decode($m[1]);
                if (!$url || !str_starts_with($url, 'http')) continue;

                foreach ($blocked as $domain) {
                    if (str_contains($url, $domain)) continue 2;
                }

                // Streamable 404 = placeholder/deleted → skip, dùng Playwright thay
                if (str_contains($url, 'streamable.com') && $this->headStatus($url) !== 200) {
                    return null;
                }

                return $url;
            }
        }

        return null;
    }

    private function getEmbedUrlViaPlaywright(string $sourceUrl): ?string
    {
        $scriptPath = base_path('scripts/hoofoot-embed.js');
        $url        = escapeshellarg($sourceUrl);
        $output     = shell_exec("node {$scriptPath} {$url} 2>/dev/null");
        if (!$output) return null;

        $data = json_decode(trim($output), true);
        return $data['embedUrl'] ?? null;
    }

    private function headStatus(string $url): int
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_NOBODY         => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_USERAGENT      => 'Mozilla/5.0',
        ]);
        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code;
    }

    // ─── Lấy recent slugs từ sitemap chính (không crawl league pages) ─
    // Dùng cho cron: nhanh, chỉ lấy entries trong N ngày gần nhất.
    public function crawlHoofootRecentSlugs(int $days = 2): array
    {
        $result = [];
        $xml = $this->fetch($this->sitemap);
        if (!$xml) return $result;

        $this->extractHoofootSlugs($xml, $result);

        $cutoff = now()->subDays($days)->format('Y-m-d');
        return array_filter($result, function ($matchId, $slug) use ($cutoff) {
            $parts   = explode('_', $slug);
            $dateStr = implode('-', array_slice($parts, -3));
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr) && $dateStr >= $cutoff;
        }, ARRAY_FILTER_USE_BOTH);
    }

    // ─── Find + save video records: Hoofoot là nguồn chính, DasFootball là backup ─
    // DasFootball CHỈ chạy khi không có video Hoofoot dùng được — nó tốn Playwright
    // nên không thử song song.
    // Match chỉ hiển thị khi có ít nhất 1 video ready (filter ở HomeController).
    public function findAndMapVideos(array $listings, int $limit = 100, bool $tryDasFootball = false): int
    {
        if (empty($listings)) return 0;

        $slugsByDate = [];
        foreach ($listings as $slug => $_) {
            $parts   = explode('_', $slug);
            $dateStr = implode('-', array_slice($parts, -3));
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
                $slugsByDate[$dateStr][] = $slug;
            }
        }

        if (empty($slugsByDate)) return 0;

        $matches = FootballMatch::with(['homeTeam', 'awayTeam', 'videos'])
            ->where('match_status', 'finished')
            ->whereIn(\DB::raw('DATE(match_date)'), array_keys($slugsByDate))
            ->orderByRaw("(SELECT COUNT(*) FROM match_videos WHERE match_videos.match_id = matches.id AND status IN ('pending','ready')) ASC")
            ->limit($limit)
            ->get();

        $mapped = 0;
        foreach ($matches as $match) {
            $dateStr      = $match->match_date->format('Y-m-d');
            $slugsForDate = $slugsByDate[$dateStr] ?? [];

            $hoofootVideo = $match->videos->where('source', 'hoofoot')->whereIn('status', ['pending', 'ready'])->first();
            $hoofootError = $match->videos->where('source', 'hoofoot')->where('status', 'error')->first();
            $hasDasFB     = $match->videos->where('source', 'dasfootball')->whereIn('status', ['pending', 'ready'])->isNotEmpty();

            // Thử Hoofoot nếu chưa có row dùng được
            if (!$hoofootVideo) {
                $hoofootSlug = $this->findMatchingSlug($match, $slugsForDate);
                if ($hoofootSlug) {
                    $sourceUrl = "{$this->hoofoot}/?match={$hoofootSlug}";
                    $embedUrl  = $this->getEmbedUrl($sourceUrl);

                    // Lần trước tải hỏng mà embed_url không đổi → tải lại cũng hỏng y
                    // hệt. Giữ nguyên 'error' và nhường luôn cho DasFootball.
                    $sameFailedUrl = $hoofootError && $hoofootError->embed_url === $embedUrl;

                    if ($embedUrl && !$sameFailedUrl) {
                        // Giữ lại kết quả để bên dưới biết Hoofoot đã có — thiếu dòng
                        // gán này thì DasFootball chạy cả khi Hoofoot vừa map xong.
                        $hoofootVideo = MatchVideo::updateOrCreate(
                            ['match_id' => $match->id, 'source' => 'hoofoot'],
                            ['source_url' => $sourceUrl, 'embed_url' => $embedUrl, 'local_path' => null, 'status' => 'pending']
                        );
                        $mapped++;
                    }
                    usleep(500_000);
                }
            }

            // Backup: chỉ chạy khi không có video Hoofoot nào dùng được
            if ($tryDasFootball && !$hoofootVideo && !$hasDasFB) {
                $video = $this->crawlDasFootball($match);
                if ($video) {
                    MatchVideo::updateOrCreate(
                        ['match_id' => $match->id, 'source' => 'dasfootball'],
                        ['video_type' => 'highlight', 'embed_url' => $video['url'], 'source_url' => $video['url'], 'status' => 'pending']
                    );
                    $mapped++;
                }
                usleep(500_000);
            }
        }

        return $mapped;
    }

    // Hoofoot dùng tên viết tắt cho một số team — map từ tên DB chuẩn (đã bỏ dấu) sang alias Hoofoot
    private const HOOFOOT_ALIASES = [
        'paris_saint_germain'         => ['psg', 'paris_sg'],
        'internazionale'              => ['inter', 'inter_milan'],
        'wolverhampton_wanderers'     => ['wolves'],
        'tottenham_hotspur'           => ['spurs'],
        'west_ham_united'             => ['west_ham'],
        'newcastle_united'            => ['newcastle'],
        'brighton_hove_albion'        => ['brighton'],
        'borussia_monchengladbach'    => ['gladbach', 'monchengladbach'],
        'atletico_de_madrid'          => ['atletico', 'atletico_madrid'],
        'atletico_madrid'             => ['atletico'],
        'sporting_cp'                 => ['sporting'],
        'benfica'                     => ['sl_benfica'],
        'rcd_mallorca'                => ['mallorca'],
        'real_betis_balompie'         => ['real_betis', 'betis'],
        'deportivo_alaves'            => ['alaves'],
        'rayo_vallecano'              => ['rayo'],
        'nottingham_forest'           => ['nottingham'],
        'olympique_lyonnais'          => ['lyon'],
        'olympique_de_marseille'      => ['marseille', 'olympique_marseille'],
    ];

    private function normalizeName(string $name): string
    {
        $name = strtolower($name);
        $name = str_replace([' ', '.', '-'], '_', $name);
        // Bỏ dấu: é→e, á→a, ü→u, v.v.
        $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name;
        return preg_replace('/[^a-z0-9_]/', '', $name);
    }

    private function findMatchingSlug(FootballMatch $match, array $slugs): ?string
    {
        $homeName  = $this->normalizeName($match->homeTeam->name);
        $awayName  = $this->normalizeName($match->awayTeam->name);
        $homeNames = array_merge([$homeName], self::HOOFOOT_ALIASES[$homeName] ?? []);
        $awayNames = array_merge([$awayName], self::HOOFOOT_ALIASES[$awayName] ?? []);

        foreach ($slugs as $slug) {
            $lower = strtolower($slug);
            foreach ($homeNames as $n) { if (str_contains($lower, $n)) return $slug; }
            foreach ($awayNames as $n) { if (str_contains($lower, $n)) return $slug; }
        }

        return null;
    }

    // ─── Crawl dasfootball.com (Next.js JS-rendered) via Playwright ─
    public function crawlDasFootball(FootballMatch $match): ?array
    {
        $home = Str::slug($match->homeTeam->name);
        $away = Str::slug($match->awayTeam->name);
        $date = is_string($match->match_date) ? substr($match->match_date, 0, 10) : $match->match_date->format('Y-m-d');

        $patterns = [
            "https://dasfootball.com/{$home}-vs-{$away}-highlights-{$date}/",
            "https://dasfootball.com/{$away}-vs-{$home}-highlights-{$date}/",
            "https://dasfootball.com/{$home}-vs-{$away}-match-highlights-{$date}/",
            "https://dasfootball.com/{$away}-vs-{$home}-match-highlights-{$date}/",
            "https://dasfootball.com/{$home}-vs-{$away}-{$date}/",
            "https://dasfootball.com/{$away}-vs-{$home}-{$date}/",
        ];

        // DasFootball là SPA — headStatus luôn 200, phải dùng Playwright để kiểm tra nội dung
        // Thử từng URL pattern đến khi Playwright trả về embed URL
        $scriptPath = base_path('scripts/dasfootball-embed.js');

        foreach ($patterns as $tryUrl) {
            $escapedUrl = escapeshellarg($tryUrl);
            $output     = shell_exec("node {$scriptPath} {$escapedUrl} 2>/dev/null");
            if (!$output) { usleep(500000); continue; }

            $data = json_decode(trim($output), true);
            if (!empty($data['embedUrl'])) {
                return ['url' => $data['embedUrl'], 'type' => $data['type'] ?? 'iframe'];
            }
            // "not found" → bỏ qua toàn bộ match luôn (không thử pattern khác)
            if (isset($data['error']) && str_contains($data['error'], 'not found')) {
                return null;
            }
            usleep(500000);
        }
        return null;
    }

    private function fetch(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER     => [
                'Accept: text/html,application/xhtml+xml,*/*',
                'Accept-Language: en-US,en;q=0.9',
            ],
        ]);
        $html = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($html && $code === 200) ? $html : null;
    }
}
