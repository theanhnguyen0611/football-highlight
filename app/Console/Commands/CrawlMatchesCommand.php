<?php

namespace App\Console\Commands;

use App\Services\CrawlService;
use App\Services\DownloadService;
use App\Services\HighlightlyService;
use Illuminate\Console\Command;

class CrawlMatchesCommand extends Command
{
    protected $signature   = 'crawl:matches {--days=3 : Number of days to sync}';
    protected $description = 'Sync matches + highlights + videos in one optimized pass';

    public function handle(HighlightlyService $highlightly, CrawlService $crawl, DownloadService $download): void
    {
        $days = (int) $this->option('days');

        $this->info('Step 1: Syncing matches & highlights from Highlightly...');
        for ($i = 0; $i < $days; $i++) {
            $date   = now()->subDays($i)->format('Y-m-d');
            $result = $highlightly->syncDate($date);
            $this->line("  {$date}: {$result['matches']} matches, {$result['highlights']} highlights");
            if ($i < $days - 1) sleep(1);
        }

        $this->info('Step 2: Syncing details for finished matches...');
        $detailed = $highlightly->syncFinishedMatchDetails(limit: 30);
        $this->line("  Detailed: {$detailed} matches");

        $this->info('Step 3: Crawling Hoofoot listings...');
        $listings = $crawl->crawlHoofootListings();
        $this->line('  Found: ' . count($listings) . ' slugs');

        $this->info('Step 4: Find & map videos (Hoofoot + DasFootball độc lập)...');
        $mapped = $crawl->findAndMapVideos($listings, limit: 100, tryDasFootball: true);
        $this->line("  Mapped: {$mapped} videos");

        $this->info('Step 5: Download all pending...');
        $downloaded = $download->downloadAllPending();
        $this->line("  Downloaded: {$downloaded} videos");

        $this->info('Step 6: Download new logos...');
        $this->call('logos:download');

        $this->info('Done!');
    }
}
