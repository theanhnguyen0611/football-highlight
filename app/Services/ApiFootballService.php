<?php
namespace App\Services;

use App\Models\FootballMatch;
use App\Models\Team;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ApiFootballService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.api_football.url', 'https://v3.football.api-sports.io');
        $this->apiKey  = config('services.api_football.key', '');
    }

    // ── HTTP helper ──────────────────────────────────────────
    private function get(string $endpoint, array $params = []): ?array
    {
        $cacheKey = 'api_football_' . md5($endpoint . json_encode($params));

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($endpoint, $params) {
            $response = Http::withHeaders([
                'x-apisports-key' => $this->apiKey,
            ])->get("{$this->baseUrl}/{$endpoint}", $params);

            if (!$response->ok()) {
                Log::error('ApiFootball: request failed', [
                    'endpoint' => $endpoint,
                    'status'   => $response->status(),
                ]);
                return null;
            }

            $data = $response->json();

            // API-Football rate limit check
            $remaining = $data['results'] ?? null;
            Log::info('ApiFootball: request ok', [
                'endpoint' => $endpoint,
                'results'  => $remaining,
            ]);

            return $data['response'] ?? null;
        });
    }

    // ── Step 1: Tìm fixture_id từ date + teams ───────────────
    public function findFixture(FootballMatch $match): ?int
    {
        if ($match->api_football_id) {
            return $match->api_football_id;
        }

        if (!$match->match_date) return null;

        $date     = $match->match_date->format('Y-m-d');
        $response = $this->get('fixtures', ['date' => $date]);

        if (!$response) return null;

        // Match bằng tên team — fuzzy match
        foreach ($response as $fixture) {
            $homeApi = strtolower($fixture['teams']['home']['name'] ?? '');
            $awayApi = strtolower($fixture['teams']['away']['name'] ?? '');
            $homeDb  = strtolower($match->homeTeam->name);
            $awayDb  = strtolower($match->awayTeam->name);

            if ($this->teamsMatch($homeDb, $homeApi) && $this->teamsMatch($awayDb, $awayApi)) {
                $fixtureId = $fixture['fixture']['id'];

                // Lưu fixture_id vào DB
                $match->update(['api_football_id' => $fixtureId]);

                // Lưu api_football_id cho teams
                $this->updateTeamApiId($match->homeTeam, $fixture['teams']['home']);
                $this->updateTeamApiId($match->awayTeam, $fixture['teams']['away']);

                Log::info('ApiFootball: fixture found', [
                    'match'      => $match->slug,
                    'fixture_id' => $fixtureId,
                ]);

                return $fixtureId;
            }
        }

        Log::warning('ApiFootball: fixture not found', ['match' => $match->slug, 'date' => $date]);
        return null;
    }

    // ── Fuzzy match tên team ─────────────────────────────────
    private function teamsMatch(string $dbName, string $apiName): bool
    {
        // Exact match
        if ($dbName === $apiName) return true;

        // Contains match
        if (str_contains($apiName, $dbName) || str_contains($dbName, $apiName)) return true;

        // Levenshtein distance <= 3
        similar_text($dbName, $apiName, $percent);
        return $percent >= 75;
    }

    private function updateTeamApiId(Team $team, array $apiTeam): void
    {
        $team->updateQuietly([
            'api_football_id'   => $apiTeam['id'],
            'api_football_name' => $apiTeam['name'],
        ]);
    }

    // ── Step 2: Lấy fixture details (venue, referee, round) ──
    public function fetchFixtureDetails(FootballMatch $match): bool
    {
        $fixtureId = $this->findFixture($match);
        if (!$fixtureId) return false;

        $response = $this->get('fixtures', ['id' => $fixtureId]);
        if (!$response || empty($response[0])) return false;

        $fixture = $response[0];

        $match->update([
            'venue'         => $fixture['fixture']['venue']['name'] ?? null,
            'referee'       => $fixture['fixture']['referee'] ?? null,
            'round'         => $fixture['league']['round'] ?? null,
            'kick_off_time' => isset($fixture['fixture']['date'])
                ? date('H:i:s', strtotime($fixture['fixture']['date']))
                : null,
        ]);

        Log::info('ApiFootball: fixture details updated', ['match' => $match->slug]);
        return true;
    }

    // ── Step 3: Lấy lineups ──────────────────────────────────
    public function fetchLineups(FootballMatch $match): ?array
    {
        $fixtureId = $this->findFixture($match);
        if (!$fixtureId) return null;

        $response = $this->get('fixtures/lineups', ['fixture' => $fixtureId]);
        if (!$response) return null;

        $lineups = [];
        foreach ($response as $teamLineup) {
            $teamId    = $teamLineup['team']['id'];
            $formation = $teamLineup['formation'] ?? null;

            $starters = array_map(fn($p) => [
                'number'     => $p['player']['number'],
                'name'       => $p['player']['name'],
                'position'   => $p['player']['pos'],
                'is_captain' => $p['player']['captain'] ?? false,
                'grid'       => $p['player']['grid'] ?? null,
            ], $teamLineup['startXI'] ?? []);

            $substitutes = array_map(fn($p) => [
                'number'   => $p['player']['number'],
                'name'     => $p['player']['name'],
                'position' => $p['player']['pos'],
            ], $teamLineup['substitutes'] ?? []);

            $lineups[] = [
                'team_id'     => $teamId,
                'team_name'   => $teamLineup['team']['name'],
                'formation'   => $formation,
                'starters'    => $starters,
                'substitutes' => $substitutes,
            ];
        }

        return $lineups;
    }

    // ── Step 4: Lấy player stats + ratings ───────────────────
    public function fetchPlayerStats(FootballMatch $match): ?array
    {
        $fixtureId = $this->findFixture($match);
        if (!$fixtureId) return null;

        $response = $this->get('fixtures/players', ['fixture' => $fixtureId]);
        if (!$response) return null;

        $stats = [];
        foreach ($response as $teamStats) {
            $teamId  = $teamStats['team']['id'];
            $players = [];

            foreach ($teamStats['players'] ?? [] as $playerData) {
                $p       = $playerData['player'];
                $s       = $playerData['statistics'][0] ?? [];
                $players[] = [
                    'id'       => $p['id'],
                    'name'     => $p['name'],
                    'photo'    => $p['photo'] ?? null,
                    'rating'   => $s['games']['rating'] ?? null,
                    'minutes'  => $s['games']['minutes'] ?? null,
                    'goals'    => $s['goals']['total'] ?? 0,
                    'assists'  => $s['goals']['assists'] ?? 0,
                    'shots'    => $s['shots']['total'] ?? 0,
                    'passes'   => $s['passes']['total'] ?? 0,
                    'tackles'  => $s['tackles']['total'] ?? 0,
                    'yellow'   => $s['cards']['yellow'] ?? 0,
                    'red'      => $s['cards']['red'] ?? 0,
                ];
            }

            $stats[] = [
                'team_id'  => $teamId,
                'team_name'=> $teamStats['team']['name'],
                'players'  => $players,
            ];
        }

        return $stats;
    }

    // ── Step 5: Lấy match events (goals, cards, subs) ────────
    public function fetchEvents(FootballMatch $match): ?array
    {
        $fixtureId = $this->findFixture($match);
        if (!$fixtureId) return null;

        $response = $this->get('fixtures/events', ['fixture' => $fixtureId]);
        if (!$response) return null;

        return array_map(fn($e) => [
            'minute'      => $e['time']['elapsed'],
            'extra'       => $e['time']['extra'] ?? null,
            'team_id'     => $e['team']['id'],
            'team_name'   => $e['team']['name'],
            'player'      => $e['player']['name'] ?? null,
            'assist'      => $e['assist']['name'] ?? null,
            'type'        => strtolower($e['type']),
            'detail'      => $e['detail'] ?? null,
        ], $response);
    }

    // ── Step 6: Lấy match statistics ─────────────────────────
    public function fetchStatistics(FootballMatch $match): ?array
    {
        $fixtureId = $this->findFixture($match);
        if (!$fixtureId) return null;

        $response = $this->get('fixtures/statistics', ['fixture' => $fixtureId]);
        if (!$response) return null;

        $stats = [];
        foreach ($response as $teamStat) {
            $teamData = [];
            foreach ($teamStat['statistics'] ?? [] as $stat) {
                $key             = strtolower(str_replace(' ', '_', $stat['type']));
                $teamData[$key]  = $stat['value'];
            }
            $stats[] = [
                'team_id'   => $teamStat['team']['id'],
                'team_name' => $teamStat['team']['name'],
                'stats'     => $teamData,
            ];
        }

        return $stats;
    }

    // ── Fetch tất cả cho 1 match ─────────────────────────────
    public function fetchAll(FootballMatch $match): array
    {
        Log::info('ApiFootball: fetchAll start', ['match' => $match->slug]);

        return [
            'details'    => $this->fetchFixtureDetails($match),
            'lineups'    => $this->fetchLineups($match),
            'players'    => $this->fetchPlayerStats($match),
            'events'     => $this->fetchEvents($match),
            'statistics' => $this->fetchStatistics($match),
        ];
    }
}
