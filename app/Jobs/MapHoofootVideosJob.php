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

// Bước "tìm video Hoofoot" tách riêng khỏi CrawlMatchesJob — không đụng quota
// Highlightly (chỉ đọc cache listings + crawl Hoofoot), nên chạy dày hơn được.
// Chỉ quét trận hôm nay + hôm qua (match_date là cột DATE, không có giờ nên
// không lọc theo giờ thực được) để mỗi lượt nhẹ, nhanh. Trận cũ hơn vẫn được
// CrawlMatchesJob (30p, không giới hạn ngày) quét dự phòng như cũ.
class MapHoofootVideosJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries   = 1;

    // Cron đẩy job dày hơn thời gian chạy → chặn chồng lượt.
    public int $uniqueFor = 580;

    public function handle(CrawlService $crawl): void
    {
        Log::info('MapHoofootVideosJob: start');

        $listings = $crawl->crawlHoofootListings();
        $mapped   = $crawl->findAndMapVideos($listings, limit: 20, tryDasFootball: false, withinDays: 2);

        Log::info('MapHoofootVideosJob: done', ['mapped' => $mapped]);
    }
}
