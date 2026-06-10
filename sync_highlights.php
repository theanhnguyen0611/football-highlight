<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$apiKey = config('services.highlightly.key');

define('TEAM_DIR',   storage_path('app/public/logos/teams'));
define('LEAGUE_DIR', storage_path('app/public/logos/leagues'));
if (!is_dir(TEAM_DIR))   mkdir(TEAM_DIR, 0755, true);
if (!is_dir(LEAGUE_DIR)) mkdir(LEAGUE_DIR, 0755, true);

$topLeagues = [
    33973=>'Premier League', 119924=>'La Liga', 67162=>'Bundesliga',
    61205=>'Serie A', 52695=>'Ligue 1', 2486=>'UCL', 3337=>'UEL',
    39079=>'FA Cup', 41632=>'League Cup', 122477=>'Copa del Rey',
    69715=>'DFB Pokal', 117371=>'Coppa Italia', 56950=>'Coupe de France',
    1635=>'World Cup', 4188=>'EURO', 8443=>'Copa America',
    5039=>'Nations League', 9294=>'Friendlies', 14400=>'CONCACAF CL',
    19506=>'Gold Cup', 75672=>'Eredivisie', 80778=>'Primeira Liga',
    109712=>'Liga Argentina', 123328=>'Jupiler Pro', 173537=>'Super Lig',
    216087=>'MLS', 223746=>'Liga MX',
];

// Dùng curl thay file_get_contents
function hlApiGet(string $url, string $apiKey): ?array {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => ["x-rapidapi-key: {$apiKey}"],
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return json_decode($res, true);
}

function hlDownloadLogo(string $url, string $path): bool {
    if (file_exists($path) && filesize($path) > 500) return true;
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15, CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $data = curl_exec($ch);
    curl_close($ch);
    if ($data && strlen($data) > 500) { file_put_contents($path, $data); return true; }
    return false;
}

function hlSyncTeam(array $t): ?\App\Models\Team {
    if (empty($t['id']) || empty($t['name'])) return null;
    $slug = \Illuminate\Support\Str::slug($t['name']);
    $localPath = null;
    if (!empty($t['logo'])) {
        $path = TEAM_DIR . "/{$slug}.png";
        if (hlDownloadLogo($t['logo'], $path)) $localPath = "logos/teams/{$slug}.png";
    }
    try {
        $team = \App\Models\Team::where('highlightly_id', $t['id'])->orWhere('slug', $slug)->first();
        if ($team) {
            $team->update(['highlightly_id' => $t['id'], 'logo_path' => $localPath ?? $team->logo_path]);
        } else {
            $team = \App\Models\Team::create([
                'name' => $t['name'], 'slug' => $slug,
                'type' => \App\Models\Team::guessTypePublic($t['name']),
                'logo_path' => $localPath, 'highlightly_id' => $t['id'],
            ]);
        }
        return $team;
    } catch (\Exception $e) {
        return \App\Models\Team::where('slug', $slug)->first();
    }
}

function hlSyncLeague(array $l): ?\App\Models\League {
    if (empty($l['id']) || empty($l['name'])) return null;
    $slug = \Illuminate\Support\Str::slug($l['name']);
    $localPath = null;
    if (!empty($l['logo'])) {
        $path = LEAGUE_DIR . "/{$slug}.png";
        if (hlDownloadLogo($l['logo'], $path)) $localPath = "logos/leagues/{$slug}.png";
    }
    return \App\Models\League::updateOrCreate(
        ['highlightly_id' => $l['id']],
        ['name' => $l['name'], 'slug' => $slug, 'logo_path' => $localPath, 'highlightly_id' => $l['id']]
    );
}

$videoCount = 0;
$matchCount = 0;

foreach ($topLeagues as $leagueId => $leagueName) {
    $res        = hlApiGet("https://soccer.highlightly.net/highlights?leagueId={$leagueId}&limit=40", $apiKey);
    $highlights = $res["data"] ?? [];
    echo "League {$leagueName}: " . count($highlights) . " highlights\n";
    if (empty($highlights)) { sleep(1); continue; }

    foreach ($highlights as $h) {
        $m = $h['match'] ?? null;
        if (!$m) continue;

        $match = \App\Models\FootballMatch::where('highlightly_id', $m['id'])->first();
        if (!$match) {
            $home   = hlSyncTeam($m['homeTeam'] ?? []);
            $away   = hlSyncTeam($m['awayTeam'] ?? []);
            $league = hlSyncLeague($m['league'] ?? []);
            if (!$home || !$away) continue;

            $date = substr($m['date'], 0, 10);
            try {
                $match = \App\Models\FootballMatch::updateOrCreate(
                    ['highlightly_id' => $m['id']],
                    [
                        'slug'           => \Illuminate\Support\Str::slug("{$home->slug}-vs-{$away->slug}-{$date}"),
                        'home_team_id'   => $home->id,
                        'away_team_id'   => $away->id,
                        'league_id'      => $league ? $league->id : null,
                        'home_score'     => $m['homeScore'] ?? null,
                        'away_score'     => $m['awayScore'] ?? null,
                        'match_date'     => $date,
                        'round'          => $m['round'] ?? null,
                        'highlightly_id' => $m['id'],
                        'status'         => 'pending',
                    ]
                );
                $matchCount++;
            } catch (\Exception $e) {
                echo "  ERR: " . $e->getMessage() . "\n";
                continue;
            }
        }

        \App\Models\MatchVideo::updateOrCreate(
            ['match_id' => $match->id, 'source_url' => $h['url']],
            ['source' => $h['source'] ?? 'highlightly', 'language' => 'en', 'status' => 'pending']
        );

        if (!empty($h['imgUrl'])) {
            $match->update(['thumbnail_url' => $h['imgUrl']]);
        }
        $videoCount++;
    }
    sleep(1);
}

echo "\nMatches created: {$matchCount}\n";
echo "Videos saved: {$videoCount}\n";
echo "Total videos: " . \App\Models\MatchVideo::count() . "\n";
echo "Total matches: " . \App\Models\FootballMatch::count() . "\n";
echo "Done!\n";
