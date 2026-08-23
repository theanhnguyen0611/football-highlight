<?php

namespace App\Console\Commands;

use App\Services\CrawlService;
use App\Services\DownloadService;
use App\Services\HighlightlyService;
use Illuminate\Console\Command;

class BootstrapCrawlCommand extends Command
{
    protected $signature   = 'crawl:bootstrap {--days=14 : Days of history to sync from Highlightly}';
    protected $description = 'Full scan — run once to seed the database with matches and videos';

    public function handle(HighlightlyService $highlightly, CrawlService $crawl, DownloadService $download): void
    {
        $days = (int) $this->option('days');

        $this->info("Step 1: Sync {$days} days from Highlightly...");
        for ($i = 0; $i < $days; $i++) {
            $date   = now()->subDays($i)->format('Y-m-d');
            $result = $highlightly->syncDate($date);
            $this->line("  {$date}: {$result['matches']} matches, {$result['highlights']} highlights");
            if ($i < $days - 1) sleep(1);
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
