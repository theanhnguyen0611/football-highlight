<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$apiKey    = config('services.highlightly.key');
$leagueDir = storage_path('app/public/logos/leagues');

$leagues = \App\Models\League::whereNull('logo_path')->get();
echo "Leagues without logo: " . $leagues->count() . "\n";

foreach ($leagues as $league) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => "https://soccer.highlightly.net/leagues/{$league->highlightly_id}",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => ["x-rapidapi-key: {$apiKey}"],
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) { echo "FAIL: {$league->name}\n"; continue; }

    $data = json_decode($res, true);
    $logoUrl = $data[0]['logo'] ?? null;

    if (!$logoUrl) {
        echo "NO LOGO: {$league->name}\n";
        continue;
    }

    // Download logo
    $slug = $league->slug;
    $path = "{$leagueDir}/{$slug}.png";
    $ch2  = curl_init();
    curl_setopt_array($ch2, [
        CURLOPT_URL => $logoUrl, CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15, CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $img = curl_exec($ch2);
    curl_close($ch2);

    if ($img && strlen($img) > 500) {
        file_put_contents($path, $img);
        $league->update(['logo_path' => "logos/leagues/{$slug}.png"]);
        echo "OK: {$league->name}\n";
    } else {
        echo "NO IMG: {$league->name}\n";
    }
    usleep(300000);
}
echo "Done!\n";
