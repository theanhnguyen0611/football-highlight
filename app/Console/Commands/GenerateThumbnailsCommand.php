<?php

namespace App\Console\Commands;

use App\Models\FootballMatch;
use App\Services\DownloadService;
use Illuminate\Console\Command;

class GenerateThumbnailsCommand extends Command
{
    protected $signature   = 'thumbnails:generate {--force : Re-generate even if thumbnail exists}';
    protected $description = 'Extract thumbnails from downloaded HLS videos';

    public function handle(DownloadService $service): void
    {
        $query = FootballMatch::whereHas('videos', fn($q) => $q->where('status', 'ready')->whereNotNull('local_path'));

        if (!$this->option('force')) {
            $query->whereNull('thumbnail_url');
        }

        $matches = $query->with('videos')->get();
        $this->info("Processing {$matches->count()} matches...");

        $ok = 0;
        foreach ($matches as $match) {
            $video   = $match->videos->where('status', 'ready')->first();
            $slug    = $match->slug;
            $outDir  = storage_path("app/public/highlights/{$slug}");

            if (!is_dir($outDir)) {
                $this->line("  skip {$slug} — no video dir");
                continue;
            }

            if ($this->option('force')) {
                $match->thumbnail_url = null;
            }

            $service->extractThumbnail($match, $outDir);

            if ($match->fresh()->thumbnail_url) {
                $this->line("  ✓ {$slug}");
                $ok++;
            } else {
                $this->warn("  ✗ {$slug}");
            }
        }

        $this->info("Done: {$ok}/{$matches->count()} thumbnails generated.");
    }
}
