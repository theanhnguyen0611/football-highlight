<?php

namespace App\Console\Commands;

use App\Services\CrawlService;
use App\Services\DownloadService;
use App\Services\HighlightlyService;
use Illuminate\Console\Command;

class BootstrapCrawlCommand extends Command
{
    protected $signature = 'crawl:bootstrap
                            {--days=14 : Days of history to sync from Highlightly}
                            {--sync-only : Chỉ chạy Step 1 — nạp match/team/league, bỏ qua video}';

    protected $description = 'Full scan — run once to seed the database with matches and videos';

    public function handle(HighlightlyService $highlightly, CrawlService $crawl, DownloadService $download): void
    {
        $days = (int) $this->option('days');

        $this->info("Step 1: Sync {$days} days from Highlightly...");
        $t0 = microtime(true);
        $totalMatches = 0;

        for ($i = 0; $i < $days; $i++) {
            $date   = now()->subDays($i)->format('Y-m-d');
            $result = $highlightly->syncDate($date);
            $totalMatches += $result['matches'];

            // Backfill dài hàng giờ — in kèm tiến độ và tốc độ để biết còn bao lâu
            $done = $i + 1;
            $eta  = $done ? (int) ((microtime(true) - $t0) / $done * ($days - $done)) : 0;
            $this->line(sprintf(
                '  [%d/%d] %s: %d matches, %d thumbnails  (teams=%d, còn ~%dm)',
                $done, $days, $date,
                $result['matches'], $result['thumbnails'],
                \App\Models\Team::count(),
                (int) round($eta / 60)
            ));

            if ($i < $days - 1) sleep(1);
        }

        $this->info(sprintf(
            'Step 1 xong: %d match, %d team, %d league trong %dm',
            $totalMatches,
            \App\Models\Team::count(),
            \App\Models\League::count(),
            (int) round((microtime(true) - $t0) / 60)
        ));

        if ($this->option('sync-only')) {
            $this->comment('--sync-only: dừng ở đây, chưa map/tải video.');
            return;
        }

        $this->info('Step 2: Sync match details...');
        $detailed = $highlightly->syncFinishedMatchDetails(limit: 50);
        $this->line("  Detailed: {$detailed} matches");

        $this->info('Step 3: Crawl all Hoofoot pages (sitemap + league pages)...');
        $listings = $crawl->crawlHoofootListings();
        $this->line('  Found: ' . count($listings) . ' slugs');

        $this->info('Step 4: Find & map videos (Hoofoot first, DasFootball fallback)...');
        $mapped = $crawl->findAndMapVideos($listings, limit: 200, tryDasFootball: true);
        $this->line("  Mapped: {$mapped} videos");

        $this->info('Step 5: Download all pending...');
        $downloaded = $download->downloadAllPending();
        $this->line("  Downloaded: {$downloaded} videos");

        $this->info('Bootstrap complete!');

    }
}
