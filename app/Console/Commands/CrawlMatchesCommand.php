<?php
namespace App\Console\Commands;

use App\Services\HighlightlyService;
use App\Services\CrawlService;
use Illuminate\Console\Command;

class CrawlMatchesCommand extends Command
{
    protected $signature   = 'crawl:matches {--days=3 : Number of days to sync}';
    protected $description = 'Sync matches from Highlightly + map videos from Hoofoot';

    public function handle(HighlightlyService $highlightly, CrawlService $crawl): void
    {
        $days = (int) $this->option('days');

        // 1. Sync matches từ Highlightly
        $this->info('Step 1: Syncing matches from Highlightly...');
        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');
            $count = $highlightly->syncMatches($date);
            $this->line("  {$date}: {$count} matches");
            sleep(1);
        }

        // 2. Sync highlights từ Highlightly (backup source)
        $this->info('Step 2: Syncing highlights from Highlightly...');
        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');
            $count = $highlightly->syncHighlights($date);
            $this->line("  {$date}: {$count} highlights");
            sleep(1);
        }

        // 3. Map hoofoot videos
        $this->info('Step 3: Mapping Hoofoot videos...');
        $mapped = $crawl->mapVideosToMatches();
        $this->line("  Mapped: {$mapped} videos");

        $this->info('Done!');
    }
}
