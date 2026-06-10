<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$apiKey = config('services.highlightly.key');
$ctx = stream_context_create(['http' => ['header' => "x-rapidapi-key: {$apiKey}\r\n"]]);
$res = json_decode(file_get_contents('https://soccer.highlightly.net/highlights?leagueId=2486&limit=1', false, $ctx), true);
$h = $res['data'][0];
$m = $h['match'];

echo "Match ID: {$m['id']}\n";
echo "Home: {$m['homeTeam']['name']}\n";
echo "Away: {$m['awayTeam']['name']}\n";

$homeSlug = \Illuminate\Support\Str::slug($m['homeTeam']['name']);
$awaySlug = \Illuminate\Support\Str::slug($m['awayTeam']['name']);
$home = \App\Models\Team::where('highlightly_id', $m['homeTeam']['id'])->orWhere('slug', $homeSlug)->first();
$away = \App\Models\Team::where('highlightly_id', $m['awayTeam']['id'])->orWhere('slug', $awaySlug)->first();

echo "Home in DB: " . ($home ? "YES id={$home->id}" : "NO") . "\n";
echo "Away in DB: " . ($away ? "YES id={$away->id}" : "NO") . "\n";

if (!$home) {
    $home = \App\Models\Team::create(['name' => $m['homeTeam']['name'], 'slug' => $homeSlug, 'type' => 'club', 'logo_path' => $m['homeTeam']['logo'], 'highlightly_id' => $m['homeTeam']['id']]);
    echo "Created home: {$home->id}\n";
}
if (!$away) {
    $away = \App\Models\Team::create(['name' => $m['awayTeam']['name'], 'slug' => $awaySlug, 'type' => 'club', 'logo_path' => $m['awayTeam']['logo'], 'highlightly_id' => $m['awayTeam']['id']]);
    echo "Created away: {$away->id}\n";
}

$date = substr($m['date'], 0, 10);
$match = \App\Models\FootballMatch::updateOrCreate(
    ['highlightly_id' => $m['id']],
    ['slug' => \Illuminate\Support\Str::slug("{$home->slug}-vs-{$away->slug}-{$date}"), 'home_team_id' => $home->id, 'away_team_id' => $away->id, 'match_date' => $date, 'highlightly_id' => $m['id'], 'status' => 'pending']
);
echo "Match: id={$match->id}\n";

\App\Models\MatchVideo::updateOrCreate(
    ['match_id' => $match->id, 'source_url' => $h['url']],
    ['source' => $h['source'], 'language' => 'en', 'status' => 'pending']
);
echo "Done! Videos: " . \App\Models\MatchVideo::count() . "\n";
