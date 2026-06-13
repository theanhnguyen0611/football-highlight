<?php
namespace App\Services;

use App\Models\Team;
use App\Models\League;
use App\Models\FootballMatch;
use App\Models\MatchVideo;
use App\Models\MatchEvent;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class HighlightlyService
{
    private string $baseUrl = 'https://soccer.highlightly.net';
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.highlightly.key');
    }

    private function get(string $endpoint, array $params = []): ?array
    {
        $url = $this->baseUrl . $endpoint;
        if ($params) $url .= '?' . http_build_query($params);

        $ctx = stream_context_create([
            'http' => [
                'header'  => "x-rapidapi-key: {$this->apiKey}\r\n",
                'timeout' => 30,
            ]
        ]);

        $res = @file_get_contents($url, false, $ctx);
        if (!$res) return null;

        return json_decode($res, true);
    }

    // ─── Sync Leagues ────────────────────────────────────────
    public function syncLeagues(): int
    {
        $res = $this->get('/leagues', ['limit' => 200]);
        $data = $res['data'] ?? [];
        $count = 0;

        foreach ($data as $l) {
            $slug = Str::slug($l['name']);
            $logoPath = $this->downloadMedia($l['logo'] ?? null, "logos/leagues/{$slug}");

            League::updateOrCreate(
                ['highlightly_id' => $l['id']],
                [
                    'name'           => $l['name'],
                    'slug'           => $slug,
                    'logo_path'      => $logoPath ?? $l['logo'] ?? null,
                    'country'        => $l['country']['name'] ?? null,
                    'highlightly_id' => $l['id'],
                ]
            );
            $count++;
        }

        Log::info("Highlightly: synced {$count} leagues");
        return $count;
    }

    // ─── Sync Matches by date ─────────────────────────────────
    public function syncMatches(string $date): int
    {
        $res = $this->get('/matches', ['date' => $date, 'limit' => 100]);
        $data = $res['data'] ?? [];
        $count = 0;

        foreach ($data as $m) {
            $this->upsertMatch($m);
            $count++;
        }

        Log::info("Highlightly: synced {$count} matches for {$date}");
        return $count;
    }

    // ─── Sync Highlights by date ──────────────────────────────
    public function syncHighlights(string $date): int
    {
        $res = $this->get('/highlights', ['date' => $date, 'limit' => 100]);
        $data = $res['data'] ?? [];
        $count = 0;

        foreach ($data as $h) {
            $matchData = $h['match'] ?? null;
            if (!$matchData) continue;

            $match = FootballMatch::where('highlightly_id', $matchData['id'])->first();
            if (!$match) {
                $match = $this->upsertMatch($matchData);
            }
            if (!$match) continue;

            // Download thumbnail nếu có
            if (!empty($h['imgUrl']) && (!$match->thumbnail_url || str_starts_with($match->thumbnail_url, 'http'))) {
                $thumbPath = $this->downloadMedia($h['imgUrl'], "thumbnails/{$match->slug}");
                if ($thumbPath) {
                    $match->update(['thumbnail_url' => '/storage/' . $thumbPath]);
                }
            }

            MatchVideo::updateOrCreate(
                ['match_id' => $match->id, 'source_url' => $h['url']],
                [
                    'source' => $h['source'] ?? 'highlightly',
                    'status' => 'pending',
                ]
            );

            $count++;
        }

        Log::info("Highlightly: synced {$count} highlights for {$date}");
        return $count;
    }

    // ─── Sync Scores from match detail events ────────────────
    public function syncScores(int $limit = 50): int
    {
        $matches = FootballMatch::whereNull('home_score')
            ->whereNotNull('highlightly_id')
            ->latest('match_date')
            ->limit($limit)
            ->get();

        $count = 0;

        foreach ($matches as $match) {
            $data = $this->get("/matches/{$match->highlightly_id}");
            if (!$data) continue;

            // API trả về array
            $detail = is_array($data) && isset($data[0]) ? $data[0] : $data;
            $events = $detail['events'] ?? [];

            if (empty($events)) continue;

            // Tính score từ goals
            $homeScore = 0;
            $awayScore = 0;

            foreach ($events as $event) {
                if ($event['type'] !== 'Goal') continue;
                if (($event['detail'] ?? '') === 'Own Goal') {
                    if ($event['team']['id'] == $match->homeTeam?->highlightly_id) $awayScore++;
                    else $homeScore++;
                } else {
                    if ($event['team']['id'] == $match->homeTeam?->highlightly_id) $homeScore++;
                    else $awayScore++;
                }
            }

            $match->update([
                'home_score' => $homeScore,
                'away_score' => $awayScore,
            ]);

            Log::info("Score synced: {$match->slug} {$homeScore}-{$awayScore}");
            $count++;
            sleep(1);
        }

        return $count;
    }

    // ─── Private helpers ─────────────────────────────────────
    private function upsertMatch(array $m): ?FootballMatch
    {
        try {
            $homeTeam = $this->upsertTeam($m['homeTeam'] ?? null);
            $awayTeam = $this->upsertTeam($m['awayTeam'] ?? null);
            if (!$homeTeam || !$awayTeam) return null;

            $league = $this->upsertLeague($m['league'] ?? null);

            $date = substr($m['date'], 0, 10);
            $slug = Str::slug("{$homeTeam->slug}-vs-{$awayTeam->slug}-{$date}");

            return FootballMatch::updateOrCreate(
                ['highlightly_id' => $m['id']],
                [
                    'slug'           => $slug,
                    'home_team_id'   => $homeTeam->id,
                    'away_team_id'   => $awayTeam->id,
                    'league_id'      => $league?->id,
                    'home_score'     => $m['homeScore'] ?? null,
                    'away_score'     => $m['awayScore'] ?? null,
                    'match_date'     => $date,
                    'round'          => $m['round'] ?? null,
                    'highlightly_id' => $m['id'],
                ]
            );
        } catch (\Exception $e) {
            Log::error("Highlightly upsertMatch error: " . $e->getMessage());
            return null;
        }
    }

    private function upsertTeam(?array $t): ?Team
    {
        if (!$t) return null;
        $slug     = Str::slug($t['name']);
        $existing = Team::where('highlightly_id', $t['id'])->first();

        $logoPath = $existing?->logo_path;
        if (!$logoPath || str_starts_with($logoPath, 'http')) {
            $logoPath = $this->downloadMedia($t['logo'] ?? null, "logos/teams/{$slug}") ?? $logoPath;
        }

        return Team::updateOrCreate(
            ['highlightly_id' => $t['id']],
            [
                'name'           => $t['name'],
                'slug'           => $slug,
                'type'           => Team::guessTypePublic($t['name']),
                'logo_path'      => $logoPath,
                'highlightly_id' => $t['id'],
            ]
        );
    }

    private function upsertLeague(?array $l): ?League
    {
        if (!$l) return null;
        $slug     = Str::slug($l['name']);
        $existing = League::where('highlightly_id', $l['id'])->first();

        $logoPath = $existing?->logo_path;
        if (!$logoPath || str_starts_with($logoPath, 'http')) {
            $logoPath = $this->downloadMedia($l['logo'] ?? null, "logos/leagues/{$slug}") ?? $logoPath;
        }

        return League::updateOrCreate(
            ['highlightly_id' => $l['id']],
            [
                'name'           => $l['name'],
                'slug'           => $slug,
                'logo_path'      => $logoPath,
                'highlightly_id' => $l['id'],
            ]
        );
    }

    private function downloadMedia(?string $url, string $basePath): ?string
    {
        if (!$url || !str_starts_with($url, 'http')) return null;

        $ext      = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'png';
        $localKey = "{$basePath}.{$ext}";

        if (Storage::disk('public')->exists($localKey)) return $localKey;

        $ctx = stream_context_create([
            'http' => ['timeout' => 15, 'header' => "User-Agent: Mozilla/5.0\r\n"],
            'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);

        $data = @file_get_contents($url, false, $ctx);
        if (!$data) return null;

        Storage::disk('public')->put($localKey, $data);
        return $localKey;
    }
}
