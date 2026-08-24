<?php
namespace App\Services;

use App\Models\FootballMatch;
use App\Models\League;
use App\Models\MatchVideo;
use App\Models\Team;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CrawlService
{
    private string $hoofoot = 'https://hoofoot.com';

    private string $sitemap = 'https://hoofoot.com/matchsitemap.php';

    public function __construct(private DownloadService $downloader) {}

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

    // ─── Extract HLS URL từ videas.fr embed page ─────────────────
    public function extractHlsUrl(string $embedUrl): ?string
    {
        $html = $this->fetch($embedUrl);
        if (!$html) return null;

        preg_match('/(https?:\/\/[^"\'\ ]+\.m3u8[^"\'\ ]*)/', $html, $m);
        return $m[1] ?? null;
    }

    // ─── Import matches từ hoofoot slugs nếu chưa có trong DB ───────
    // Dùng cho pre-season matches mà Highlightly không trả về.
    public function importHoofootSlugsAsMatches(array $listings = []): int
    {
        if (empty($listings)) $listings = $this->crawlHoofootListings();
        $cutoff    = now()->subDays(30)->format('Y-m-d');
        $league    = League::firstOrCreate(
            ['slug' => 'club-friendlies'],
            ['name' => 'Club Friendlies', 'highlightly_id' => 99998]
        );
        $imported  = 0;

        foreach ($listings as $slug => $matchId) {
            $parts = explode('_', $slug);
            if (count($parts) < 5) continue;

            // Tách date (3 phần cuối: YYYY, MM, DD)
            $dd    = array_pop($parts);
            $mm    = array_pop($parts);
            $yyyy  = array_pop($parts);
            if (!is_numeric($yyyy) || !is_numeric($mm) || !is_numeric($dd)) continue;
            $dateStr = "{$yyyy}-{$mm}-{$dd}";
            if ($dateStr < $cutoff) continue; // chỉ import 30 ngày gần nhất

            // Tách home/away quanh từ 'v' (hoofoot dùng _v_ làm separator)
            $vIdx = array_search('v', $parts);
            if ($vIdx === false || $vIdx === 0 || $vIdx >= count($parts) - 1) continue;

            $homeName = implode(' ', array_slice($parts, 0, $vIdx));
            $awayName = implode(' ', array_slice($parts, $vIdx + 1));
            if (!$homeName || !$awayName) continue;

            // Kiểm tra DB đã có match này chưa (theo slug)
            $matchSlug = Str::slug("{$homeName}-vs-{$awayName}-{$dateStr}");
            if (FootballMatch::where('slug', $matchSlug)->exists()) continue;

            // Cũng kiểm tra ngược (home/away có thể bị đảo)
            $reverseSlug = Str::slug("{$awayName}-vs-{$homeName}-{$dateStr}");
            if (FootballMatch::where('slug', $reverseSlug)->exists()) continue;

            $homeTeam = Team::findOrCreateByName($homeName);
            $awayTeam = Team::findOrCreateByName($awayName);

            FootballMatch::create([
                'slug'         => $matchSlug,
                'home_team_id' => $homeTeam->id,
                'away_team_id' => $awayTeam->id,
                'league_id'    => $league->id,
                'match_date'   => $dateStr,
                'match_status' => 'finished',
            ]);

            Log::info("Imported hoofoot match: {$homeName} vs {$awayName} on {$dateStr}");
            $imported++;
        }

        return $imported;
    }

    // ─── Map hoofoot video vào match đã có trong DB (flow gốc) ──────────
    public function mapVideosToMatches(array $listings = []): int
    {
        if (empty($listings)) $listings = $this->crawlHoofootListings();
        $mapped = 0;

        foreach ($listings as $slug => $matchId) {
            $parts   = explode('_', $slug);
            $dateStr = implode('-', array_slice($parts, -3));
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) continue;

            $dbMatches = FootballMatch::with(['homeTeam', 'awayTeam'])
                ->whereDate('match_date', $dateStr)
                ->get();

            foreach ($dbMatches as $match) {
                $homeName  = strtolower(str_replace([' ', '.', '-'], '_', $match->homeTeam->name));
                $awayName  = strtolower(str_replace([' ', '.', '-'], '_', $match->awayTeam->name));
                $slugLower = strtolower($slug);

                if (!str_contains($slugLower, $homeName) && !str_contains($slugLower, $awayName)) {
                    continue;
                }

                $existing = MatchVideo::where('match_id', $match->id)
                    ->where('source', 'hoofoot')
                    ->first();

                if ($existing && ($existing->status === 'ready' || $existing->embed_url)) {
                    $mapped++;
                    break;
                }

                $sourceUrl = "{$this->hoofoot}/?match={$slug}";
                $embedUrl  = $this->getEmbedUrl($sourceUrl);

                if (!$embedUrl) {
                    usleep(500000);
                    continue;
                }

                MatchVideo::updateOrCreate(
                    ['match_id' => $match->id, 'source' => 'hoofoot'],
                    ['source_url' => $sourceUrl, 'embed_url' => $embedUrl, 'local_path' => null, 'status' => 'pending']
                );
                $mapped++;
                break;
            }

            usleep(500000);
        }

        return $mapped;
    }

    // ─── Backfill embed_url cho videos pending chưa có URL ───────────
    public function backfillEmbedUrls(): int
    {
        $videos = MatchVideo::where('status', 'pending')
            ->where('source', 'hoofoot')
            ->whereNull('embed_url')
            ->whereNotNull('source_url')
            ->get();

        $filled = 0;
        foreach ($videos as $video) {
            $embedUrl = $this->getEmbedUrl($video->source_url);
            if ($embedUrl) {
                $video->update(['embed_url' => $embedUrl]);
                $filled++;
            }
            sleep(2);
        }
        return $filled;
    }

    // ─── Download tất cả hoofoot videos pending ──────────────────────
    public function downloadPendingVideos(): int
    {
        $videos = MatchVideo::where('status', 'pending')
            ->where('source', 'hoofoot')
            ->whereNotNull('embed_url')
            ->whereNull('local_path')
            ->with('match')
            ->get();

        $downloaded = 0;
        foreach ($videos as $video) {
            $embed  = $video->embed_url;
            $hlsUrl = $this->downloader->getHlsUrl($embed);
            if ($hlsUrl) {
                if ($this->downloader->downloadHls($video, $hlsUrl)) $downloaded++;
                continue;
            }
            if (str_contains($embed, 'youtube.com') || str_contains($embed, 'youtu.be')) {
                if ($this->downloader->downloadYoutube($video, $embed)) $downloaded++;
            }
        }
        return $downloaded;
    }

    // ─── DasFootball fallback cho matches chưa có video ──────────────
    public function mapDasFootballFallback(int $limit = 20): int
    {
        $matches = FootballMatch::with(['homeTeam', 'awayTeam', 'league'])
            ->where('match_status', 'finished')
            ->whereNotNull('home_score')
            ->whereHas('league', fn($q) => $q->whereNotIn('name', ['International Friendlies', 'Club Friendlies']))
            ->whereDoesntHave('videos', fn($q) => $q->where('source', 'dasfootball')->whereIn('status', ['pending', 'ready']))
            ->orderByDesc('match_date')
            ->limit($limit)
            ->get();

        $mapped = 0;
        foreach ($matches as $match) {
            $video = $this->crawlDasFootball($match);
            if (!$video) { usleep(500000); continue; }

            MatchVideo::updateOrCreate(
                ['match_id' => $match->id, 'source' => 'dasfootball'],
                ['video_type' => 'highlight', 'embed_url' => $video['url'], 'source_url' => $video['url'], 'status' => 'pending']
            );
            $mapped++;
            usleep(500000);
        }
        return $mapped;
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

    // ─── Unified: find + save video records — thử cả Hoofoot VÀ DasFootball độc lập ─
    // Mỗi source được check riêng: nếu chưa có thì thử, không skip source còn lại.
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
            $hasDasFB     = $match->videos->where('source', 'dasfootball')->whereIn('status', ['pending', 'ready'])->isNotEmpty();

            // Thử Hoofoot nếu chưa có
            if (!$hoofootVideo) {
                $hoofootSlug = $this->findMatchingSlug($match, $slugsForDate);
                if ($hoofootSlug) {
                    $sourceUrl = "{$this->hoofoot}/?match={$hoofootSlug}";
                    $embedUrl  = $this->getEmbedUrl($sourceUrl);
                    if ($embedUrl) {
                        MatchVideo::updateOrCreate(
                            ['match_id' => $match->id, 'source' => 'hoofoot'],
                            ['source_url' => $sourceUrl, 'embed_url' => $embedUrl, 'local_path' => null, 'status' => 'pending']
                        );
                        $mapped++;
                    }
                    usleep(500_000);
                }
            }

            // DasFootball chỉ làm backup: thử khi Hoofoot không có hoặc bị lỗi
            $hoofootMissing = !$hoofootVideo || $match->videos->where('source', 'hoofoot')->where('status', 'error')->isNotEmpty();
            if ($tryDasFootball && $hoofootMissing && !$hasDasFB) {
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
        'paris_saint_germain'         => ['psg'],
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

    private function post(string $url, array $data): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($data),
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER     => [
                'Accept: */*',
                'X-Requested-With: XMLHttpRequest',
                'Referer: https://hoofoot.com/',
            ],
        ]);
        $html = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($html && $code === 200) ? $html : null;
    }
}
