<?php
namespace App\Console\Commands;

use App\Models\League;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SetLeagueBackgroundsCommand extends Command
{
    protected $signature   = 'leagues:set-backgrounds';
    protected $description = 'Set background_url for leagues based on files in storage/app/public/leagues/backgrounds/';

    // slug => filename (must match what you upload to storage/app/public/leagues/backgrounds/)
    private array $map = [
        'premier-league'             => 'premier-league.webp',
        'la-liga'                    => 'la-liga.webp',
        'bundesliga'                 => 'bundesliga.webp',
        'serie-a'                    => 'serie-a.webp',
        'ligue-1'                    => 'ligue-1.webp',
        'uefa-champions-league'      => 'champions-league.webp',
        'uefa-europa-league'         => 'europa-league.webp',
        'fa-cup'                     => 'fa-cup.webp',
        'league-cup'                 => 'league-cup.webp',
        'copa-del-rey'               => 'copa-del-rey.webp',
        'dfb-pokal'                  => 'dfb-pokal.webp',
        'coppa-italia'               => 'coppa-italia.webp',
        'coupe-de-france'            => 'coupe-de-france.webp',
        'world-cup'                  => 'world-cup.webp',
        'euro-championship'          => 'euro.webp',
        'copa-america'               => 'copa-america.webp',
        'uefa-nations-league'        => 'nations-league.webp',
        'international-friendlies'   => 'international-friendlies.webp',
        'club-friendlies'            => 'club-friendlies.webp',
        'concacaf-champions-league'  => 'concacaf-cl.webp',
        'concacaf-gold-cup'          => 'gold-cup.webp',
        'eredivisie'                 => 'eredivisie.webp',
        'primeira-liga'              => 'primeira-liga.webp',
        'super-lig'                  => 'super-lig.webp',
        'major-league-soccer'        => 'mls.webp',
        'saudi-pro-league'           => 'saudi-pro-league.webp',
        'world-cup-qualifier'        => 'world-cup-qualifier.webp',
        // Các league chưa có trong DB — sẽ tự map khi sync
        'liga-profesional-argentina' => 'liga-argentina.webp',
    ];

    public function handle(): void
    {
        $dir = storage_path('app/public/leagues/backgrounds');
        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $this->info("Upload images to: {$dir}");
        $this->line('');

        $updated = 0;
        $missing = 0;

        foreach ($this->map as $slug => $filename) {
            $league = League::where('slug', $slug)->first();
            if (!$league) {
                $this->warn("  [skip] league not found: {$slug}");
                continue;
            }

            $path = "{$dir}/{$filename}";
            if (!File::exists($path)) {
                $this->line("  [missing] {$filename} -> {$league->name}");
                $missing++;
                continue;
            }

            $league->update(['background_url' => "leagues/backgrounds/{$filename}"]);
            $this->info("  [ok] {$filename} -> {$league->name}");
            $updated++;
        }

        $this->line('');
        $this->info("Updated: {$updated}, Missing: {$missing}");
    }
}
