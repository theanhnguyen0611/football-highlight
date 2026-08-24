<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Services\CrawlService;
use Illuminate\Console\Command;

class ScanHoofootMismatchesCommand extends Command
{
    protected $signature   = 'hoofoot:scan-mismatches';
    protected $description = 'So sánh tên team trong Hoofoot slugs với DB → tìm alias cần thêm vào HOOFOOT_ALIASES';

    public function handle(CrawlService $crawl): void
    {
        $this->info('Fetching Hoofoot listings...');
        $listings = $crawl->crawlHoofootListings();
        $this->info('Slugs found: ' . count($listings));

        // Extract team names từ slug: "Manchester_City_v_Bournemouth_2026_08_23"
        // → split trên "_v_", bỏ date ở cuối
        $hoofootTeams = [];
        foreach (array_keys($listings) as $slug) {
            // Tách phần date (3 số cuối): _YYYY_MM_DD
            $core = preg_replace('/_\d{4}_\d{2}_\d{2}$/', '', $slug);

            // Split trên _v_ (case-insensitive)
            $parts = preg_split('/_v_/i', $core, 2);
            if (count($parts) === 2) {
                $hoofootTeams[strtolower($parts[0])] = $parts[0];
                $hoofootTeams[strtolower($parts[1])] = $parts[1];
            }
        }

        $this->info('Unique Hoofoot team names: ' . count($hoofootTeams));

        // Load tất cả DB teams (normalize bỏ dấu)
        $dbTeams = Team::all()->mapWithKeys(fn($t) => [
            $this->normalize($t->name) => $t->name
        ]);

        // Tìm Hoofoot team nào không match bất kỳ DB team nào
        $this->newLine();
        $this->warn('Hoofoot names không match DB team nào:');
        $this->newLine();

        $unmatched = [];
        foreach ($hoofootTeams as $key => $original) {
            // Check xem DB có team nào chứa key này không (hoặc key chứa DB name)
            $found = $dbTeams->first(function ($dbName, $dbKey) use ($key) {
                return str_contains($key, $dbKey) || str_contains($dbKey, $key);
            });

            if (!$found) {
                $unmatched[$key] = $original;
            }
        }

        if (empty($unmatched)) {
            $this->info('Tất cả Hoofoot teams đều match được với DB!');
            return;
        }

        // Sort để dễ đọc
        ksort($unmatched);

        $this->table(
            ['Hoofoot name (gốc)', 'Key cần alias'],
            array_map(fn($orig, $key) => [$orig, $key], $unmatched, array_keys($unmatched))
        );

        $this->newLine();
        $this->info('→ Với các team trên, tìm DB name tương ứng rồi thêm vào HOOFOOT_ALIASES trong CrawlService.php');
        $this->info('   Ví dụ: \'paris_saint_germain\' => [\'psg\']');
    }

    private function normalize(string $name): string
    {
        $name = strtolower($name);
        $name = str_replace([' ', '.', '-'], '_', $name);
        $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name;
        return preg_replace('/[^a-z0-9_]/', '', $name);
    }
}
