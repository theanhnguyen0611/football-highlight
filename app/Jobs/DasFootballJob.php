<?php

namespace App\Jobs;

use App\Services\CrawlService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DasFootballJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1200;
    public int $tries   = 1;

    public function handle(CrawlService $crawl): void
    {
        Log::info('DasFootballJob: start');

        $listings = $crawl->crawlHoofootRecentSlugs(days: 3);
        $mapped   = $crawl->findAndMapVideos($listings, limit: 40, tryDasFootball: true);

        Log::info('DasFootballJob: done', ['mapped' => $mapped]);
    }
}
