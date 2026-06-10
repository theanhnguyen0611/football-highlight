<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$apiKey = config('services.api_football.key');
$dir = storage_path('app/public/logos');
if (!is_dir($dir)) mkdir($dir, 0755, true);

$leagueIds = [1, 4, 9, 2, 3, 39, 140, 78, 135, 61];

foreach ($leagueIds as $leagueId) {
    $url = "https://v3.football.api-sports.io/teams?league={$leagueId}&season=2024";
    $ctx = stream_context_create(['http' => ['header' => "x-apisports-key: {$apiKey}\r\n"]]);
    $res = @file_get_contents($url, false, $ctx);
    if (!$res) { echo "FAIL league {$leagueId}\n"; continue; }

    $teams = json_decode($res, true)['response'] ?? [];
    echo "League {$leagueId}: " . count($teams) . " teams\n";

    foreach ($teams as $t) {
        $apiId = $t['team']['id'];
        $name  = $t['team']['name'];
        $logo  = $t['team']['logo'];
        $slug  = \Illuminate\Support\Str::slug($name);

        $team = \App\Models\Team::where('slug', $slug)
            ->orWhere('api_football_id', $apiId)
            ->first();

        if (!$team) {
            $team = \App\Models\Team::create([
                'name'            => $name,
                'slug'            => $slug,
                'type'            => \App\Models\Team::guessTypePublic($name),
                'primary_color'   => '#ffffff',
                'secondary_color' => '#000000',
                'api_football_id' => $apiId,
            ]);
            echo "  NEW: {$name}\n";
        } else {
            $team->update(['api_football_id' => $apiId]);
        }

        $path = "{$dir}/{$team->slug}.png";
        if (!file_exists($path) || filesize($path) < 500) {
            $data = @file_get_contents($logo);
            if ($data && strlen($data) > 500) {
                file_put_contents($path, $data);
                echo "  LOGO: {$name}\n";
            }
        }

        $team->update(['logo_path' => "logos/{$team->slug}.png"]);
        usleep(100000);
    }
    sleep(1);
}

echo "\nTotal: " . \App\Models\Team::count() . " teams\n";
echo "With logos: " . \App\Models\Team::whereNotNull('logo_path')->count() . "\n";
echo "Done!\n";
