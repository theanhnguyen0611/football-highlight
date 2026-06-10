<?php
namespace App\Services;

use App\Models\FootballMatch;
use App\Models\MatchVideo;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CrawlService
{
    private string $hoofoot = 'https://hoofoot.com';

    // ─── Tìm video hoofoot cho 1 match ───────────────────────
    public function findHoofootVideo(FootballMatch $match): ?string
    {
        $home = $match->homeTeam->name;
        $away = $match->awayTeam->name;
        $date = $match->match_date->format('Y-m-d');

        // Build hoofoot URL pattern
        $slug = "{$home}_v_{$away}_{$date}";
        $slug = str_replace(' ', '_', $slug);
        $url  = "{$this->hoofoot}/?match={$slug}";

        $html = $this->fetch($url);
        if (!$html) return null;

        // Extract HLS embed URL
        preg_match('/src=["\']([^"\']*embed[^"\']*)["\']/', $html, $m);
        return $m[1] ?? null;
    }

    // ─── Crawl hoofoot listings để tìm matches mới ───────────
    public function crawlHoofootListings(): array
    {
        $html = $this->fetch($this->hoofoot);
        if (!$html) return [];

        preg_match_all('/\?match=([A-Za-z0-9_]+)/', $html, $matches);
        return array_unique($matches[1] ?? []);
    }

    // ─── Map hoofoot video vào match đã có trong DB ──────────
    public function mapVideosToMatches(): int
    {
        $slugs = $this->crawlHoofootListings();
        $mapped = 0;

        foreach ($slugs as $slug) {
            $url  = "{$this->hoofoot}/?match={$slug}";
            $html = $this->fetch($url);
            if (!$html) continue;

            // Extract embed URL
            preg_match('/src=["\']([^"\']*(?:embed|hls)[^"\']*)["\']/', $html, $m);
            $embedUrl = $m[1] ?? null;
            if (!$embedUrl) continue;

            // Parse match info từ slug: TeamA_v_TeamB_YYYY_MM_DD
            $parts = explode('_', $slug);
            $dateStr = implode('-', array_slice($parts, -3));
            if (!preg_match('/\d{4}-\d{2}-\d{2}/', $dateStr)) continue;

            // Tìm match trong DB theo date
            $matches = FootballMatch::where('match_date', $dateStr)
                ->where('status', 'pending')
                ->get();

            foreach ($matches as $match) {
                $homeName = strtolower(str_replace(' ', '_', $match->homeTeam->name));
                $awayName = strtolower(str_replace(' ', '_', $match->awayTeam->name));
                $slugLower = strtolower($slug);

                if (str_contains($slugLower, $homeName) || str_contains($slugLower, $awayName)) {
                    MatchVideo::updateOrCreate(
                        ['match_id' => $match->id, 'source' => 'hoofoot'],
                        [
                            'source_url' => $url,
                            'local_path' => null,
                            'status'     => 'pending',
                        ]
                    );
                    $mapped++;
                    break;
                }
            }

            usleep(500000); // 0.5s delay
        }

        return $mapped;
    }

    private function fetch(string $url): ?string
    {
        $ctx = stream_context_create([
            'http' => [
                'header'  => "User-Agent: Mozilla/5.0\r\n",
                'timeout' => 15,
            ]
        ]);
        return @file_get_contents($url, false, $ctx) ?: null;
    }
}
