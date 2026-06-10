<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$apiKey = config('services.api_football.key');
$dir = storage_path('app/public/logos');
if (!is_dir($dir)) mkdir($dir, 0755, true);

// Lấy tất cả teams từ các giải hot
$leagueIds = [
    39,   // Premier League
    140,  // La Liga
    78,   // Bundesliga
    135,  // Serie A
    61,   // Ligue 1
    2,    // Champions League
    3,    // Europa League
    848,  // Conference League
    1,    // World Cup
    4,    // EURO
    9,    // Copa America
];

$allTeams = [];

foreach ($leagueIds as $leagueId) {
    $url = "https://v3.football.api-sports.io/teams?league={$leagueId}&season=2024";
    $ctx = stream_context_create(['http' => ['header' => "x-apisports-key: {$apiKey}\r\n"]]);
    $res = @file_get_contents($url, false, $ctx);
    if (!$res) { echo "FAIL league {$leagueId}\n"; continue; }
    
    $data = json_decode($res, true);
    $teams = $data['response'] ?? [];
    echo "League {$leagueId}: " . count($teams) . " teams\n";
    
    foreach ($teams as $t) {
        $id   = $t['team']['id'];
        $name = $t['team']['name'];
        $logo = $t['team']['logo'];
        if (!isset($allTeams[$id])) {
            $allTeams[$id] = ['name' => $name, 'logo' => $logo];
        }
    }
    sleep(1);
}

echo "\nTotal unique teams: " . count($allTeams) . "\n\n";

// Download logos và upsert vào DB
foreach ($allTeams as $apiId => $team) {
    $slug = \Illuminate\Support\Str::slug($team['name']);
    $path = "{$dir}/{$slug}.png";

    // Download logo
    $data = @file_get_contents($team['logo']);
    if ($data && strlen($data) > 500) {
        file_put_contents($path, $data);
        echo "Logo: {$team['name']}\n";
    }

    // Upsert team vào DB
    \App\Models\Team::updateOrCreate(
        ['slug' => $slug],
        [
            'name'            => $team['name'],
            'slug'            => $slug,
            'api_football_id' => $apiId,
            'logo_path'       => "logos/{$slug}.png",
        ]
    );

    usleep(100000);
}

echo "\nDone! " . count($allTeams) . " teams processed.\n";
