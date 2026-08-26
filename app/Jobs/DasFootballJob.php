<?php

namespace App\Jobs;

use App\Services\CrawlService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DasFootballJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;
    public int $tries   = 1;

    // Cron đẩy job dày hơn thời gian chạy → chặn chồng lượt.
    // Khoá hết hạn cùng lúc timeout để job treo không kẹt mãi.
    public int $uniqueFor = 1800;

    public function handle(CrawlService $crawl): void
    {
        Log::info('DasFootballJob: start');

        $listings = $crawl->crawlHoofootRecentSlugs(days: 14);
        $mapped   = $crawl->findAndMapVideos($listings, limit: 40, tryDasFootball: true);

        Log::info('DasFootballJob: done', ['mapped' => $mapped]);
    }
}
