<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

function syncTeam(array $t): \App\Models\Team {
    $slug = \Illuminate\Support\Str::slug($t['name']);
    $team = \App\Models\Team::where('highlightly_id', $t['id'])
        ->orWhere('slug', $slug)
        ->first();
    if ($team) {
        $team->update(['highlightly_id' => $t['id'], 'logo_path' => $t['logo']]);
    } else {
        $team = \App\Models\Team::create([
            'name' => $t['name'], 'slug' => $slug,
            'type' => 'club', 'logo_path' => $t['logo'],
            'highlightly_id' => $t['id'],
        ]);
    }
    return $team;
}

$apiKey = config('services.highlightly.key');
$ctx = stream_context_create(['http' => ['header' => "x-rapidapi-key: {$apiKey}\r\n"]]);
$res = json_decode(file_get_contents('https://soccer.highlightly.net/highlights?leagueId=33973&limit=5', false, $ctx), true);

foreach ($res['data'] as $h) {
    $m        = $h['match'];
    $date     = substr($m['date'], 0, 10);
    $homeTeam = syncTeam($m['homeTeam']);
    $awayTeam = syncTeam($m['awayTeam']);

    $league = \App\Models\League::updateOrCreate(
        ['highlightly_id' => $m['league']['id']],
        ['name' => $m['league']['name'], 'slug' => \Illuminate\Support\Str::slug($m['league']['name']), 'highlightly_id' => $m['league']['id']]
    );
    $match = \App\Models\FootballMatch::updateOrCreate(
        ['highlightly_id' => $m['id']],
        [
            'slug'           => \Illuminate\Support\Str::slug("{$homeTeam->slug}-vs-{$awayTeam->slug}-{$date}"),
            'home_team_id'   => $homeTeam->id,
            'away_team_id'   => $awayTeam->id,
            'league_id'      => $league->id,
            'match_date'     => $date,
            'round'          => $m['round'] ?? null,
            'highlightly_id' => $m['id'],
            'status'         => 'pending',
        ]
    );
    \App\Models\MatchVideo::updateOrCreate(
        ['match_id' => $match->id, 'source_url' => $h['url']],
        ['source' => $h['source'], 'status' => 'pending']
    );
    echo "OK: {$homeTeam->name} vs {$awayTeam->name}\n";
}
echo "Done! Videos: " . \App\Models\MatchVideo::count() . "\n";
