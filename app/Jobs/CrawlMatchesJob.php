<?php

namespace App\Jobs;

use App\Exceptions\HighlightlyQuotaException;
use App\Services\CrawlService;
use App\Services\DownloadService;
use App\Services\HighlightlyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class CrawlMatchesJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 2400;
    public int $tries   = 1;

    // Cron đẩy job dày hơn thời gian chạy → chặn chồng lượt.
    // Khoá hết hạn cùng lúc timeout để job treo không kẹt mãi.
    public int $uniqueFor = 2400;

    public function handle(HighlightlyService $highlightly, CrawlService $crawl): void
    {
        Log::info('CrawlMatchesJob: start (cron mode)');

        // Hết quota là chuyện bình thường (backfill vừa ăn hết) — log rồi bỏ qua
        // phần Highlightly, đừng ném để mỗi 30 phút lại đẻ một failed_job.
        try {
            for ($i = 0; $i < 2; $i++) {
                $date   = now()->subDays($i)->format('Y-m-d');
                $result = $highlightly->syncDate($date);
                Log::info("CrawlMatchesJob: syncDate {$date}", $result);
                if ($i === 0) sleep(1);
            }

            // Venue + events: cron trước đây không gọi nên trận sync qua cron
            // không bao giờ có chi tiết (chỉ có khi chạy tay crawl:matches).
            $detailed = $highlightly->syncFinishedMatchDetails(limit: 30);
            Log::info('CrawlMatchesJob: details synced', ['count' => $detailed]);
        } catch (HighlightlyQuotaException $e) {
            Log::warning('CrawlMatchesJob: bỏ qua Highlightly, ' . $e->getMessage());
        }

        // Hoofoot: dùng full listings (bao gồm league pages)
        $listings = $crawl->crawlHoofootListings();
        Log::info('CrawlMatchesJob: listings', ['count' => count($listings)]);

        // DasFootball chạy riêng trong DasFootballJob — không gọi ở đây
        $mapped = $crawl->findAndMapVideos($listings, limit: 60, tryDasFootball: false);

        // Thumbnail lưu trên web server, không đẩy sang SX65 — ảnh nhỏ, nginx
        // serve trực tiếp rẻ hơn đi vòng qua CDN.
        Artisan::call('thumbnails:download');
        Artisan::call('logos:download');

        Log::info('CrawlMatchesJob: done', ['mapped' => $mapped]);
    }
}
