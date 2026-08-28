<?php

namespace App\Console\Commands;

use App\Models\League;
use App\Models\Team;
use Illuminate\Console\Command;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class DownloadLogosCommand extends Command
{
    protected $signature   = 'logos:download';
    protected $description = 'Download team/league logos từ Highlightly về SX65, convert sang WebP, update DB';

    public function handle(): void
    {
        $localBase = storage_path('app/public/logos');
        @mkdir("{$localBase}/teams",   0755, true);
        @mkdir("{$localBase}/leagues", 0755, true);

        // ── Teams ──────────────────────────────────────────────────────
        $teams = Team::whereNotNull('logo_path')
            ->where('logo_path', 'like', 'http%')
            ->get();

        $this->info("Teams: {$teams->count()}");
        $bar = $this->output->createProgressBar($teams->count());

        foreach ($teams as $team) {
            $file = "teams/{$team->id}.webp";
            $path = "{$localBase}/{$file}";

            if ($this->download($team->logo_path, $path)) {
                $team->update(['logo_path' => $file]);
            }
            $bar->advance();
        }
        $bar->finish();

        // ── Leagues ────────────────────────────────────────────────────
        $leagues = League::whereNotNull('logo_path')
            ->where('logo_path', 'like', 'http%')
            ->get();

        $this->newLine();
        $this->info("Leagues: {$leagues->count()}");
        $bar = $this->output->createProgressBar($leagues->count());

        foreach ($leagues as $league) {
            $file = "leagues/{$league->id}.webp";
            $path = "{$localBase}/{$file}";

            if ($this->download($league->logo_path, $path)) {
                $league->update(['logo_path' => $file]);
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();

        $this->info('Done! Logos served from web server.');
    }

    private function download(string $url, string $path): bool
    {
        if (file_exists($path) && filesize($path) > 0) return true;

        $data = @file_get_contents($url, false, stream_context_create([
            'http' => ['timeout' => 15, 'header' => "User-Agent: Mozilla/5.0\r\n"],
        ]));

        if (!$data) return false;

        try {
            $manager = new ImageManager(Driver::class);
            $encoded = $manager->decodeBinary($data)->encodeUsingFileExtension('webp', quality: 85);
            file_put_contents($path, (string) $encoded);
        } catch (\Throwable $e) {
            // Nguồn không phải ảnh raster (vd SVG) — lưu tạm nguyên bản để không mất logo
            file_put_contents($path, $data);
        }

        return filesize($path) > 0;
    }
}
