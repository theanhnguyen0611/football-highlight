<?php
namespace App\Console\Commands;

use App\Models\FootballMatch;
use App\Models\MatchLineup;
use App\Models\MatchEvent;
use App\Models\MatchStatistic;
use App\Models\MatchPlayerStat;
use App\Services\ApiFootballService;
use Illuminate\Console\Command;

class FetchMatchDetailsCommand extends Command
{
    protected $signature   = 'fetch:details {--match= : Specific match slug} {--limit=10 : Number of matches}';
    protected $description = 'Fetch lineup, events, stats từ API-Football';

    public function handle(ApiFootballService $api): void
    {
        $query = FootballMatch::with(['homeTeam', 'awayTeam'])
            ->whereNotNull('match_date');

        if ($slug = $this->option('match')) {
            $query->where('slug', $slug);
        } else {
            $query->whereNull('api_football_id')
                  ->latest('match_date')
                  ->limit((int) $this->option('limit'));
        }

        $matches = $query->get();
        $this->info("Processing {$matches->count()} matches...");

        foreach ($matches as $match) {
            $this->line("\n[{$match->slug}]");

            // 1. Fixture details
            $ok = $api->fetchFixtureDetails($match);
            if (!$ok) {
                $this->warn("  No fixture found on API-Football");
                continue;
            }
            $match->refresh();
            $this->info("  fixture_id: {$match->api_football_id} | venue: {$match->venue}");

            // 2. Lineups
            $lineups = $api->fetchLineups($match);
            if ($lineups) {
                foreach ($lineups as $lineup) {
                    $team = $match->homeTeam->api_football_id == $lineup['team_id']
                        ? $match->homeTeam : $match->awayTeam;

                    MatchLineup::updateOrCreate(
                        ['match_id' => $match->id, 'team_id' => $team->id],
                        [
                            'formation'   => $lineup['formation'],
                            'starters'    => json_encode($lineup['starters']),
                            'substitutes' => json_encode($lineup['substitutes']),
                        ]
                    );
                }
                $this->info("  lineups: saved");
            }

            // 3. Events
            $events = $api->fetchEvents($match);
            if ($events) {
                MatchEvent::where('match_id', $match->id)->delete();
                foreach ($events as $e) {
                    $team = $match->homeTeam->api_football_id == $e['team_id']
                        ? $match->homeTeam : $match->awayTeam;

                    MatchEvent::create([
                        'match_id'  => $match->id,
                        'team_id'   => $team->id,
                        'minute'    => $e['minute'],
                        'extra'     => $e['extra'],
                        'type'      => $e['type'],
                        'detail'    => $e['detail'],
                        'player'    => $e['player'],
                        'assist'    => $e['assist'],
                    ]);
                }
                $this->info("  events: " . count($events) . " saved");
            }

            // 4. Statistics
            $stats = $api->fetchStatistics($match);
            if ($stats) {
                foreach ($stats as $teamStat) {
                    $team = $match->homeTeam->api_football_id == $teamStat['team_id']
                        ? $match->homeTeam : $match->awayTeam;

                    MatchStatistic::updateOrCreate(
                        ['match_id' => $match->id, 'team_id' => $team->id],
                        ['stats' => json_encode($teamStat['stats'])]
                    );
                }
                $this->info("  statistics: saved");
            }

            // 5. Player stats
            $players = $api->fetchPlayerStats($match);
            if ($players) {
                MatchPlayerStat::where('match_id', $match->id)->delete();
                foreach ($players as $teamPlayers) {
                    $team = $match->homeTeam->api_football_id == $teamPlayers['team_id']
                        ? $match->homeTeam : $match->awayTeam;

                    foreach ($teamPlayers['players'] as $p) {
                        MatchPlayerStat::create([
                            'match_id'      => $match->id,
                            'team_id'       => $team->id,
                            'api_player_id' => $p['id'],
                            'name'          => $p['name'],
                            'rating'        => $p['rating'],
                            'minutes'       => $p['minutes'],
                            'goals'         => $p['goals'] ?? 0,
                            'assists'       => $p['assists'] ?? 0,
                            'shots'         => $p['shots'] ?? 0,
                            'passes'        => $p['passes'] ?? 0,
                            'tackles'       => $p['tackles'] ?? 0,
                            'yellow_cards'  => $p['yellow'] ?? 0,
                            'red_cards'     => $p['red'] ?? 0,
                        ]);
                    }
                }
                $this->info("  player stats: saved");
            }

            sleep(2); // Tránh rate limit
        }

        $this->info("\nDone!");
    }
}
