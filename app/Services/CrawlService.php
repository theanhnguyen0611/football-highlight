<?php
namespace App\Services;

use App\Models\FootballMatch;
use App\Models\MatchVideo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CrawlService
{
    private string $hoofoot = 'https://hoofoot.com';

    private string $sitemap = 'https://hoofoot.com/matchsitemap.php';

    // Quét sitemap + 50 trang league tốn ~1 phút, mà CrawlMatchesJob (30 phút)
    // và DasFootballJob (1 giờ) đều gọi hàm này riêng → cache lại để dùng
    // chung, đỡ tăng gấp 3 lần traffic lên Hoofoot mỗi giờ.
    private const LISTINGS_CACHE_TTL = 3600;

    // ─── Crawl sitemap → league pages → [slug => match_id] ──────
    public function crawlHoofootListings(): array
    {
        // Không cache khi rỗng — sitemap lỗi tạm thời (mạng/Hoofoot down vài
        // phút) mà cache cả rỗng thì chặn map video suốt 1 giờ luôn.
        $cached = Cache::get('hoofoot:listings');
        if ($cached) return $cached;

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

        if ($result) Cache::put('hoofoot:listings', $result, self::LISTINGS_CACHE_TTL);

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

    // ─── Find + save video records: Hoofoot là nguồn chính, DasFootball là backup ─
    // DasFootball CHỈ chạy khi không có video Hoofoot dùng được, và trận đã đá
    // đủ 2 ngày (nhường thời gian cho Hoofoot cập nhật trước).
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

        // Ưu tiên trận MỚI trước (match_date DESC): sitemap Hoofoot trải dài
        // 2024→nay, không giới hạn ngày, nên hàng nghìn trận cũ từ backfill —
        // phần lớn 0 video vì Hoofoot không phủ — nếu xếp theo "số video ASC"
        // sẽ chiếm hết $limit mỗi lượt, chặn vĩnh viễn trận vừa đá (đã có 1
        // video DasFootball) không bao giờ được thử lại Hoofoot nữa.
        $query = FootballMatch::with(['homeTeam', 'awayTeam', 'videos'])
            ->where('match_status', 'finished');

        // Nhánh Hoofoot: chỉ cần xét trận nằm trong ngày Hoofoot đã liệt kê,
        // đỡ query thừa. Nhánh DasFootball: KHÔNG lọc theo ngày Hoofoot — nó tự
        // build URL từ tên đội + ngày, không phụ thuộc listings Hoofoot. Nếu
        // vẫn lọc theo $slugsByDate thì những ngày Hoofoot không phủ (0 slug)
        // sẽ bị loại khỏi query, khiến DasFootball không bao giờ được thử cho
        // các trận ngày đó dù đáng lẽ nó vẫn backup được.
        if (!$tryDasFootball) {
            $query->whereIn(\DB::raw('DATE(match_date)'), array_keys($slugsByDate));
        }

        $matches = $query
            ->orderByDesc('match_date')
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

            // Backup: chỉ chạy khi không có video Hoofoot nào dùng được, và trận đã
            // đá đủ 2 ngày — nhường thời gian cho Hoofoot cập nhật trước, tránh
            // DasFootball "cướp" link trước rồi trận bị hạ ưu tiên, không thử lại
            // Hoofoot nữa (do ORDER BY số video ASC + LIMIT ở query bên trên).
            $matchAgeDays = $match->match_date->diffInDays(now());
            if ($tryDasFootball && !$hoofootVideo && !$hasDasFB && $matchAgeDays >= 2) {
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
        'atletico_de_madrid'          => ['atletico_madrid'],
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

    // "Manchester_City_v_Bournemouth_2026_08_23" → ['manchester_city', 'bournemouth']
    private function splitHoofootSlug(string $slug): array
    {
        $parts = explode('_', strtolower($slug));
        if (count($parts) < 5) return [null, null];

        $parts = array_slice($parts, 0, -3);          // bỏ Y_m_d
        $vIdx  = array_search('v', $parts, true);     // Hoofoot dùng _v_ làm dấu phân cách
        if ($vIdx === false || $vIdx === 0 || $vIdx >= count($parts) - 1) return [null, null];

        return [
            implode('_', array_slice($parts, 0, $vIdx)),
            implode('_', array_slice($parts, $vIdx + 1)),
        ];
    }

    // 2 = trùng khít, 1 = một bên là tiền tố theo token của bên kia, 0 = không khớp
    private function sideMatch(?string $side, array $names): int
    {
        if ($side === null || $side === '') return 0;

        foreach ($names as $n) {
            if ($side === $n) return 2;
        }
        foreach ($names as $n) {
            if (str_starts_with($side, $n . '_') || str_starts_with($n, $side . '_')) return 1;
        }

        return 0;
    }

    private function findMatchingSlug(FootballMatch $match, array $slugs): ?string
    {
        $homeName  = $this->normalizeName($match->homeTeam->name);
        $awayName  = $this->normalizeName($match->awayTeam->name);
        $homeNames = array_merge([$homeName], self::HOOFOOT_ALIASES[$homeName] ?? []);
        $awayNames = array_merge([$awayName], self::HOOFOOT_ALIASES[$awayName] ?? []);

        // Bản cũ trả về slug ĐẦU TIÊN chỉ cần MỘT đội khớp. Khi DB chỉ có ~150
        // trận thì hiếm khi sai, nhưng sau backfill một năm thì mỗi ngày có hàng
        // chục trận và alias ngắn dễ đụng nhau: 'inter' (Internazionale) khớp cả
        // Inter_Miami, 'sporting' khớp cả Sporting_Kansas_City.
        //
        // Giờ: khớp CẢ HAI đội thì chắc chắn đúng, nhận ngay. Chỉ khớp một đội
        // thì chỉ nhận khi trong ngày đó không còn ứng viên nào khác — mơ hồ thì
        // thà bỏ qua còn hơn gán nhầm video sang trận khác.
        $partial = [];

        foreach ($slugs as $slug) {
            [$slugHome, $slugAway] = $this->splitHoofootSlug($slug);
            if ($slugHome === null) continue;

            // Đối chiếu từng vế thay vì str_contains cả chuỗi: 'inter' phải khớp
            // vế "inter" của Bologna_v_Inter, chứ không khớp "inter_miami".
            $h = max($this->sideMatch($slugHome, $homeNames), $this->sideMatch($slugAway, $homeNames));
            $a = max($this->sideMatch($slugHome, $awayNames), $this->sideMatch($slugAway, $awayNames));

            if ($h > 0 && $a > 0) return $slug;

            // Khớp một vế: chỉ nhận khi khớp CHÍNH XÁC (=2). Khớp kiểu tiền tố
            // (=1) là chỗ Inter_Miami giả dạng Inter, không đủ tin.
            if (max($h, $a) === 2) $partial[] = $slug;
        }

        if (count($partial) === 1) return $partial[0];

        if (count($partial) > 1) {
            Log::info('Hoofoot: bỏ qua vì nhiều slug khớp một phần', [
                'match'   => $match->slug,
                'slugs'   => $partial,
            ]);
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

        // DasFootball (Next.js) server-render sẵn contentUrl trong JSON-LD
        // VideoObject ngay trong HTML gốc → thử curl trước (rẻ, không cần
        // Chromium). Chỉ fallback Playwright khi curl không tìm ra gì chắc
        // chắn (trang cần JS mới lộ nguồn video).
        $scriptPath = base_path('scripts/dasfootball-embed.js');

        foreach ($patterns as $tryUrl) {
            $curl = $this->getDasFootballEmbedViaCurl($tryUrl);

            if ($curl['status'] === 'found') {
                Log::info('DasFootball: nguồn tìm được (curl)', [
                    'match' => $match->slug,
                    'chọn'  => $curl['type'],
                ]);
                return ['url' => $curl['url'], 'type' => $curl['type']];
            }

            // "not found" → bỏ qua toàn bộ match luôn (không thử pattern khác)
            if ($curl['status'] === 'not_found') {
                return null;
            }

            // curl không chắc (rỗng/không tìm ra nguồn) → fallback Playwright
            $escapedUrl = escapeshellarg($tryUrl);
            $output     = shell_exec("node {$scriptPath} {$escapedUrl} 2>/dev/null");
            if (!$output) { usleep(500000); continue; }

            $data = json_decode(trim($output), true);
            if (!empty($data['embedUrl'])) {
                // Script đã xếp hạng hls > mp4 > streamable > iframe > youtube.
                // Log cả danh sách để biết trang có gì mà mình bỏ qua.
                Log::info('DasFootball: nguồn tìm được (playwright)', [
                    'match'   => $match->slug,
                    'chọn'    => $data['type'] ?? 'iframe',
                    'sources' => array_map(
                        fn($s) => ($s['type'] ?? '?') . ' ' . ($s['url'] ?? ''),
                        $data['sources'] ?? []
                    ),
                ]);

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

    // DasFootball nhúng sẵn link video thật vào JSON-LD VideoObject (để SEO
    // đọc được) ngay trong HTML gốc (server-rendered) — đọc thẳng field này
    // thay vì quét toàn trang tìm URL. Không tìm thấy thì để crawlDasFootball()
    // tự fallback sang Playwright (case cần chạy JS mới lộ nguồn).
    private function getDasFootballEmbedViaCurl(string $url): array
    {
        $html = $this->fetch($url);
        if (!$html) return ['status' => 'unknown'];

        // DasFootball là SPA, trang không tồn tại vẫn trả HTTP 200. Không dùng
        // str_contains 'not found' chung chung — text đó nằm sẵn trong bundle
        // JS của route 404 (Next.js App Router gộp chung), lộ ra ở CẢ trang hợp
        // lệ. Marker đáng tin duy nhất: thẻ <title> render server-side.
        if (str_contains($html, '<title>Page not found</title>')) {
            return ['status' => 'not_found'];
        }

        if (preg_match('/"contentUrl":"([^"]+)"/', $html, $m)
            || preg_match('/"embedUrl":"([^"]+)"/', $html, $m)) {
            $videoUrl = str_replace('\\/', '/', $m[1]);
            $type     = str_contains($videoUrl, '.m3u8') ? 'hls' : (str_contains($videoUrl, '.mp4') ? 'mp4' : 'iframe');

            return ['status' => 'found', 'url' => $videoUrl, 'type' => $type];
        }

        return ['status' => 'empty'];
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
