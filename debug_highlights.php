<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$apiKey = config('services.highlightly.key');

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => 'https://soccer.highlightly.net/highlights?leagueId=33973&limit=3',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_HTTPHEADER     => ["x-rapidapi-key: {$apiKey}"],
    CURLOPT_SSL_VERIFYPEER => false,
]);
$res  = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Code: {$code}\n";
$data = json_decode($res, true);
echo "Count: " . count($data['data'] ?? []) . "\n";
echo "Raw (first 200): " . substr($res, 0, 200) . "\n";
