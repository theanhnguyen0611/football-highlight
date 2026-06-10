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
    33973  => 'Premier League',
    119924 => 'La Liga',
    67162  => 'Bundesliga',
    61205  => 'Serie A',
    52695  => 'Ligue 1',
    2486   => 'UEFA Champions League',
    3337   => 'UEFA Europa League',
    39079  => 'FA Cup',
    41632  => 'League Cup',
    122477 => 'Copa del Rey',
    69715  => 'DFB Pokal',
    117371 => 'Coppa Italia',
    56950  => 'Coupe de France',
    56099  => 'Coupe de la Ligue',
    1635   => 'World Cup',
    4188   => 'Euro Championship',
    8443   => 'Copa America',
    5039   => 'UEFA Nations League',
    9294   => 'Friendlies',
    14400  => 'CONCACAF Champions League',
    19506  => 'CONCACAF Gold Cup',
    75672  => 'Eredivisie',
    80778  => 'Primeira Liga',
    109712 => 'Liga Profesional Argentina',
    123328 => 'Jupiler Pro League',
    173537 => 'Super Lig',
    216087 => 'Major League Soccer',
    223746 => 'Liga MX',
    25463  => 'WC Qualification Africa',
    26314  => 'WC Qualification Asia',
    27165  => 'WC Qualification CONCACAF',
    28016  => 'WC Qualification Europe',
    29718  => 'WC Qualification South America',
];

function apiGet(string $url, string $apiKey): ?array {
    $ctx = stream_context_create(['http' => [
        'header'  => "x-rapidapi-key: {$apiKey}\r\n",
        'timeout' => 30,
    ]]);
    $res = @file_get_contents($url, false, $ctx);
    return $res ? json_decode($res, true) : null;
}

function downloadLogo(string $url, string $path): bool {
    if (file_exists($path) && filesize($path) > 500) return true;
    $data = @file_get_contents($url);
    if ($data && strlen($data) > 500) {
        file_put_contents($path, $data);
        return true;
    }
    return false;
}

function syncTeam(array $t): ?\App\Models\Team {
    if (empty($t['id']) || empty($t['name'])) return null;
    $slug      = \Illuminate\Support\Str::slug($t['name']);
    $localPath = null;
    if (!empty($t['logo'])) {
        $path = TEAM_DIR . "/{$slug}.png";
        if (downloadLogo($t['logo'], $path)) {
            $localPath = "logos/teams/{$slug}.png";
        }
    }
    try {
        $team = \App\Models\Team::where('highlightly_id', $t['id'])
            ->orWhere('slug', $slug)
            ->first();
        if ($team) {
            $team->update([
                'highlightly_id' => $t['id'],
                'logo_path'      => $localPath ?? $team->logo_path,
            ]);
        } else {
            $team = \App\Models\Team::create([
                'name'           => $t['name'],
                'slug'           => $slug,
                'type'           => \App\Models\Team::guessTypePublic($t['name']),
                'logo_path'      => $localPath,
                'highlightly_id' => $t['id'],
            ]);
        }
        return $team;
    } catch (\Exception $e) {
        // Slug conflict — try find by slug only
        return \App\Models\Team::where('slug', $slug)->first();
    }
}

function syncLeague(array $l): ?\App\Models\League {
    if (empty($l['id']) || empty($l['name'])) return null;
    $slug      = \Illuminate\Support\Str::slug($l['name']);
    $localPath = null;
    if (!empty($l['logo'])) {
        $path = LEAGUE_DIR . "/{$slug}.png";
        if (downloadLogo($l['logo'], $path)) {
            $localPath = "logos/leagues/{$slug}.png";
        }
    }
    return \App\Models\League::updateOrCreate(
        ['highlightly_id' => $l['id']],
        [
            'name'           => $l['name'],
            'slug'           => $slug,
            'logo_path'      => $localPath,
            'country'        => $l['country']['name'] ?? null,
            'highlightly_id' => $l['id'],
        ]
    );
}

function upsertMatch(array $m): ?\App\Models\FootballMatch {
    $homeTeam = syncTeam($m['homeTeam'] ?? []);
    $awayTeam = syncTeam($m['awayTeam'] ?? []);
    $league   = syncLeague($m['league'] ?? []);
    if (!$homeTeam || !$awayTeam) return null;

    $matchDate = substr($m['date'], 0, 10);
    $slug      = \Illuminate\Support\Str::slug("{$homeTeam->slug}-vs-{$awayTeam->slug}-{$matchDate}");

    return \App\Models\FootballMatch::updateOrCreate(
        ['highlightly_id' => $m['id']],
        [
            'slug'           => $slug,
            'home_team_id'   => $homeTeam->id,
            'away_team_id'   => $awayTeam->id,
            'league_id'      => $league ? $league->id : null,
            'home_score'     => $m['homeScore'] ?? null,
            'away_score'     => $m['awayScore'] ?? null,
            'match_date'     => $matchDate,
            'round'          => $m['round'] ?? null,
            'highlightly_id' => $m['id'],
            'status'         => 'pending',
        ]
    );
}

// ─── Step 1: Sync league logos ───────────────────────────
echo "=== Step 1: Syncing leagues ===\n";
foreach ($topLeagues as $id => $name) {
    $res = apiGet("https://soccer.highlightly.net/leagues/{$id}", $apiKey);
    $l   = is_array($res) ? ($res[0] ?? null) : null;
    if ($l) {
        syncLeague($l);
        echo "  OK: {$name}\n";
    } else {
        $slug = \Illuminate\Support\Str::slug($name);
        \App\Models\League::updateOrCreate(
            ['highlightly_id' => $id],
            ['name' => $name, 'slug' => $slug, 'highlightly_id' => $id]
        );
        echo "  BASIC: {$name}\n";
    }
    usleep(300000);
}
echo "Leagues: " . \App\Models\League::count() . "\n\n";

// ─── Step 2: Sync highlights by league ───────────────────
echo "=== Step 2: Syncing highlights ===\n";
$videoCount = 0;
$matchCount = 0;

foreach ($topLeagues as $leagueId => $leagueName) {
    $res        = apiGet("https://soccer.highlightly.net/highlights?leagueId={$leagueId}&limit=40", $apiKey);
    $highlights = $res['data'] ?? [];
    if (empty($highlights)) { usleep(300000); continue; }
    echo "League {$leagueName}: " . count($highlights) . " highlights\n";

    foreach ($highlights as $h) {
        $matchData = $h['match'] ?? null;
        if (!$matchData) continue;

        $match = \App\Models\FootballMatch::where('highlightly_id', $matchData['id'])->first();
        if (!$match) {
            try {
                $match = upsertMatch($matchData);
                if ($match) $matchCount++;
            } catch (\Exception $e) {
                echo "  ERROR upsertMatch: " . $e->getMessage() . "
";
                continue;
            }
        }
        if (!$match) continue;

        \App\Models\MatchVideo::updateOrCreate(
            ['match_id' => $match->id, 'source_url' => $h['url']],
            [
                'source'   => $h['source'] ?? 'highlightly',
                'language' => 'en',
                'status'   => 'pending',
            ]
        );

        if (!empty($h['imgUrl'])) {
            $match->update(['thumbnail_url' => $h['imgUrl']]);
        }

        $videoCount++;
    }
    sleep(1);
}

echo "\n=== Summary ===\n";
echo "Leagues: "  . \App\Models\League::count() . "\n";
echo "Teams: "    . \App\Models\Team::count() . "\n";
echo "Matches: "  . \App\Models\FootballMatch::count() . " ({$matchCount} new)\n";
echo "Videos: "   . \App\Models\MatchVideo::count() . " ({$videoCount} new)\n";
echo "Done!\n";
