<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$apiKey = config('services.highlightly.key');
$dir    = storage_path('app/public/logos/teams');
if (!is_dir($dir)) mkdir($dir, 0755, true);

$leagueDir = storage_path('app/public/logos/leagues');
if (!is_dir($leagueDir)) mkdir($leagueDir, 0755, true);

// 1. Sync leagues
echo "=== Syncing Leagues ===\n";
$url = "https://soccer.highlightly.net/leagues?limit=200";
$ctx = stream_context_create(['http' => ['header' => "x-rapidapi-key: {$apiKey}\r\n"]]);
$res = json_decode(@file_get_contents($url, false, $ctx), true);
$leagues = $res['data'] ?? [];
echo count($leagues) . " leagues\n";

foreach ($leagues as $l) {
    $slug = \Illuminate\Support\Str::slug($l['name']);
    if ($l['logo']) {
        $path = "{$leagueDir}/{$slug}.png";
        if (!file_exists($path) || filesize($path) < 500) {
            $data = @file_get_contents($l['logo']);
            if ($data && strlen($data) > 500) file_put_contents($path, $data);
        }
    }
    \App\Models\League::updateOrCreate(
        ['highlightly_id' => $l['id']],
        [
            'name'           => $l['name'],
            'slug'           => $slug,
            'logo_path'      => $l['logo'] ?? null,
            'country'        => $l['country']['name'] ?? null,
            'highlightly_id' => $l['id'],
        ]
    );
}
echo "Leagues done\n\n";

// 2. Sync teams
echo "=== Syncing Teams ===\n";
$dates = [];
for ($i = 0; $i < 30; $i++) {
    $dates[] = date('Y-m-d', strtotime("-{$i} days"));
}

$teamCount = 0;
foreach ($dates as $date) {
    $url = "https://soccer.highlightly.net/matches?date={$date}&limit=100";
    $ctx = stream_context_create(['http' => ['header' => "x-rapidapi-key: {$apiKey}\r\n"]]);
    $res = json_decode(@file_get_contents($url, false, $ctx), true);
    $matches = $res['data'] ?? [];
    if (empty($matches)) continue;
    echo "Date {$date}: " . count($matches) . " matches\n";

    foreach ($matches as $m) {
        foreach (['homeTeam', 'awayTeam'] as $side) {
            $t = $m[$side] ?? null;
            if (!$t) continue;

            $slug    = \Illuminate\Support\Str::slug($t['name']);
            $logoUrl = $t['logo'] ?? null;

            // Download logo
            if ($logoUrl) {
                $path = "{$dir}/{$slug}.png";
                if (!file_exists($path) || filesize($path) < 500) {
                    $data = @file_get_contents($logoUrl);
                    if ($data && strlen($data) > 500) {
                        file_put_contents($path, $data);
                        $teamCount++;
                    }
                }
            }

            // Tìm theo highlightly_id hoặc slug — tránh duplicate
            $team = \App\Models\Team::where('highlightly_id', $t['id'])
                ->orWhere('slug', $slug)
                ->first();

            if ($team) {
                $team->update([
                    'highlightly_id' => $t['id'],
                    'logo_path'      => $logoUrl ?? $team->logo_path,
                ]);
            } else {
                \App\Models\Team::create([
                    'name'           => $t['name'],
                    'slug'           => $slug,
                    'type'           => \App\Models\Team::guessTypePublic($t['name']),
                    'logo_path'      => $logoUrl,
                    'highlightly_id' => $t['id'],
                ]);
            }
        }
    }
    sleep(1);
}

echo "\nTeam logos downloaded: {$teamCount}\n";
echo "Total teams: " . \App\Models\Team::count() . "\n";
echo "Total leagues: " . \App\Models\League::count() . "\n";
echo "Done!\n";
