<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$apiKey = config('services.api_football.key');
$dir = storage_path('app/public/logos');

// Fetch Nations League + World Cup qualifiers để lấy hết national teams
$sources = [
    "https://v3.football.api-sports.io/teams?league=5&season=2024",
    "https://v3.football.api-sports.io/teams?league=5&season=2022",
    "https://v3.football.api-sports.io/teams?league=5&season=2020",
];

foreach ($sources as $url) {
    $ctx = stream_context_create(['http' => ['header' => "x-apisports-key: {$apiKey}\r\n"]]);
    $res = json_decode(@file_get_contents($url, false, $ctx), true);
    $teams = $res['response'] ?? [];
    echo count($teams) . " teams from " . $url . "\n";

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
                'type'            => 'national',
                'primary_color'   => '#ffffff',
                'secondary_color' => '#000000',
                'api_football_id' => $apiId,
            ]);
            echo "  NEW: {$name}\n";
        }

        $path = "{$dir}/{$team->slug}.png";
        if (!file_exists($path) || filesize($path) < 500) {
            $data = @file_get_contents($logo);
            if ($data && strlen($data) > 500) {
                file_put_contents($path, $data);
                echo "  LOGO: {$team->name}\n";
            }
        }

        $team->update([
            'api_football_id' => $apiId,
            'logo_path'       => "logos/{$team->slug}.png",
            'type'            => 'national',
        ]);

        usleep(200000);
    }
    sleep(1);
}

echo "\nWith logos: " . \App\Models\Team::whereNotNull('logo_path')->count() . "\n";
echo "Done!\n";
