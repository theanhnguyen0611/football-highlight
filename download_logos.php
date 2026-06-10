<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$dir = storage_path('app/public/logos');
if (!is_dir($dir)) mkdir($dir, 0755, true);

$teams = \App\Models\Team::whereNotNull('api_football_id')->get();
echo "Teams with API ID: " . $teams->count() . "\n";

foreach ($teams as $team) {
    $url  = "https://media.api-sports.io/football/teams/{$team->api_football_id}.png";
    $path = "{$dir}/{$team->slug}.png";
    $data = @file_get_contents($url);
    if ($data && strlen($data) > 1000) {
        file_put_contents($path, $data);
        $team->update(['logo_path' => "logos/{$team->slug}.png"]);
        echo "OK: {$team->name} (" . strlen($data) . " bytes)\n";
    } else {
        echo "FAIL: {$team->name}\n";
    }
    usleep(300000);
}
echo "Done!\n";
