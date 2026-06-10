<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$apiKey = config('services.highlightly.key');
$ctx = stream_context_create(['http' => ['header' => "x-rapidapi-key: {$apiKey}\r\n", 'timeout' => 30]]);

$leagues = [33973 => 'PL', 2486 => 'UCL', 67162 => 'Bundesliga'];

foreach ($leagues as $id => $name) {
    $url = "https://soccer.highlightly.net/highlights?leagueId={$id}&limit=5";
    $res = json_decode(@file_get_contents($url, false, $ctx), true);
    $count = count($res['data'] ?? []);
    echo "{$name}: {$count} highlights\n";
    if ($count > 0) {
        $h = $res['data'][0];
        $matchId = $h['match']['id'];
        $match = \App\Models\FootballMatch::where('highlightly_id', $matchId)->first();
        echo "  Match {$matchId}: " . ($match ? "EXISTS id={$match->id}" : "NOT IN DB") . "\n";
        echo "  URL: {$h['url']}\n";
    }
    sleep(2);
}
