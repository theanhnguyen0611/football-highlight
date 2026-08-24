<?php

namespace App\Jobs;

use App\Services\CrawlService;
use App\Services\DownloadService;
use App\Services\HighlightlyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CrawlMatchesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900;
    public int $tries   = 1;

    public function handle(HighlightlyService $highlightly, CrawlService $crawl): void
    {
        Log::info('CrawlMatchesJob: start (cron mode)');

        // Sync Highlightly today + yesterday
        for ($i = 0; $i < 2; $i++) {
            $date   = now()->subDays($i)->format('Y-m-d');
            $result = $highlightly->syncDate($date);
            Log::info("CrawlMatchesJob: syncDate {$date}", $result);
            if ($i === 0) sleep(1);
        }

        // Hoofoot: dùng full listings (bao gồm league pages)
        $listings = $crawl->crawlHoofootListings();
        Log::info('CrawlMatchesJob: listings', ['count' => count($listings)]);

        // DasFootball chạy riêng trong DasFootballJob — không gọi ở đây
        $mapped = $crawl->findAndMapVideos($listings, limit: 60, tryDasFootball: false);

        Log::info('CrawlMatchesJob: done', ['mapped' => $mapped]);
    }
}
