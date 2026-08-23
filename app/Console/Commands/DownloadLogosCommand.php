<?php

namespace App\Console\Commands;

use App\Models\League;
use App\Models\Team;
use Illuminate\Console\Command;

class DownloadLogosCommand extends Command
{
    protected $signature   = 'logos:download';
    protected $description = 'Download team/league logos từ Highlightly về SX65, update DB';

    public function handle(): void
    {
        $ssh  = config('services.cdn.sx65_ssh');
        $base = config('services.cdn.sx65_path');
        $cdn  = config('services.cdn.url');

        if (!$ssh || !$base || !$cdn) {
            $this->error('SX65 hoặc CDN chưa cấu hình.');
            return;
        }

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
            $ext  = $this->ext($team->logo_path);
            $file = "teams/{$team->id}{$ext}";
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
            $ext  = $this->ext($league->logo_path);
            $file = "leagues/{$league->id}{$ext}";
            $path = "{$localBase}/{$file}";

            if ($this->download($league->logo_path, $path)) {
                $league->update(['logo_path' => $file]);
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();

        // ── Rsync lên SX65 ─────────────────────────────────────────────
        $this->info('Syncing to SX65...');
        $cmd = sprintf(
            'rsync -az --mkpath --no-perms %s/ %s:%s/logos/',
            escapeshellarg($localBase),
            $ssh,
            $base
        );
        shell_exec($cmd);

        $this->info('Done! Logos served from CDN.');
    }

    private function download(string $url, string $path): bool
    {
        if (file_exists($path) && filesize($path) > 0) return true;

        $data = @file_get_contents($url, false, stream_context_create([
            'http' => ['timeout' => 15, 'header' => "User-Agent: Mozilla/5.0\r\n"],
        ]));

        if (!$data) return false;
        file_put_contents($path, $data);
        return filesize($path) > 0;
    }

    private function ext(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return $ext ? ".{$ext}" : '.png';
    }
}
