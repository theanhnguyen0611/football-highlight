<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$apiKey = config('services.highlightly.key');

function apiGet(string $url, string $apiKey): ?array {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => ["x-rapidapi-key: {$apiKey}"],
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "  [apiGet] code={$code} len=" . strlen($res) . "\n";
    if (!$res || $code !== 200) return null;
    return json_decode($res, true);
}

$res = apiGet("https://soccer.highlightly.net/highlights?leagueId=33973&limit=3", $apiKey);
echo "Count: " . count($res['data'] ?? []) . "\n";
