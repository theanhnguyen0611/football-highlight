<?php
namespace App\Services;

use App\Models\Team;
use App\Models\League;
use App\Models\FootballMatch;
use App\Models\MatchVideo;
use App\Models\MatchEvent;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

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
            League::updateOrCreate(
                ['highlightly_id' => $l['id']],
                [
                    'name'            => $l['name'],
                    'slug'            => $slug,
                    'logo_path'       => $l['logo'] ?? null,
                    'country'         => $l['country']['name'] ?? null,
                    'highlightly_id'  => $l['id'],
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

            // Tìm match trong DB
            $match = FootballMatch::where('highlightly_id', $matchData['id'])->first();
            if (!$match) {
                $match = $this->upsertMatch($matchData);
            }
            if (!$match) continue;

            // Lưu video
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

    // ─── Sync Events (goals + red cards only) ────────────────
    public function syncEvents(int $matchId, int $highlightlyMatchId): void
    {
        // Highlightly không có events endpoint riêng
        // Dùng API-Football nếu có match_id tương ứng
        // Tạm thời skip — implement sau khi có API-Football mapping
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
                    'slug'            => $slug,
                    'home_team_id'    => $homeTeam->id,
                    'away_team_id'    => $awayTeam->id,
                    'league_id'       => $league?->id,
                    'home_score'      => $m['homeScore'] ?? null,
                    'away_score'      => $m['awayScore'] ?? null,
                    'match_date'      => $date,
                    'round'           => $m['round'] ?? null,
                    'highlightly_id'  => $m['id'],
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
        $slug = Str::slug($t['name']);
        return Team::updateOrCreate(
            ['highlightly_id' => $t['id']],
            [
                'name'           => $t['name'],
                'slug'           => $slug,
                'type'           => Team::guessTypePublic($t['name']),
                'logo_path'      => $t['logo'] ?? null,
                'highlightly_id' => $t['id'],
            ]
        );
    }

    private function upsertLeague(?array $l): ?League
    {
        if (!$l) return null;
        $slug = Str::slug($l['name']);
        return League::updateOrCreate(
            ['highlightly_id' => $l['id']],
            [
                'name'           => $l['name'],
                'slug'           => $slug,
                'logo_path'      => $l['logo'] ?? null,
                'highlightly_id' => $l['id'],
            ]
        );
    }
}
