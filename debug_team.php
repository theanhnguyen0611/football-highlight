<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Check 1 team đã tạo
$team = \App\Models\Team::first();
echo "Team: {$team->name}\n";
echo "highlightly_id: " . ($team->highlightly_id ?? 'NULL') . "\n";
echo "slug: {$team->slug}\n";

// Check fillable
$m = new \App\Models\Team();
echo "Fillable: " . implode(', ', $m->getFillable()) . "\n";
