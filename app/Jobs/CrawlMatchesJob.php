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
            $result = $highlightly->syncDate(now()->format('Y-m-d'));
            Log::info('CrawlMatchesJob: syncDate ' . now()->format('Y-m-d'), $result);

            // "Hôm qua" gần như không đổi sau vài giờ đầu ngày (trận đã đá xong
            // hết, chốt số liệu rồi) — chỉ requery trong khung 6h đầu (UTC, app
            // timezone) để bắt trận đá muộn xuyên ngày, tránh fetch lại 48
            // lần/ngày suốt cả ngày (tốn ~nửa tổng request Highlightly cho dữ
            // liệu gần như không đổi).
            if (now()->hour < 6) {
                sleep(1);
                $yDate   = now()->subDay()->format('Y-m-d');
                $yResult = $highlightly->syncDate($yDate);
                Log::info("CrawlMatchesJob: syncDate {$yDate}", $yResult);
            }

            // Venue + events: cron trước đây không gọi nên trận sync qua cron
            // không bao giờ có chi tiết (chỉ có khi chạy tay crawl:matches).
            $detailed = $highlightly->syncFinishedMatchDetails(limit: 30);
            Log::info('CrawlMatchesJob: details synced', ['count' => $detailed]);
        } catch (HighlightlyQuotaException $e) {
            Log::warning('CrawlMatchesJob: bỏ qua Highlightly, ' . $e->getMessage());
        }

        // Hoofoot: dùng full listings (bao gồm league pages) — chỉ dùng cho
        // nhánh fallback bên trong findAndMapVideos(), DasFootball (chính)
        // không cần listings.
        $listings = $crawl->crawlHoofootListings();
        Log::info('CrawlMatchesJob: listings', ['count' => count($listings)]);

        // DasFootball luôn được thử trước; Hoofoot chỉ chạy như fallback sau
        // 2 ngày nếu DasFootball vẫn chưa có (xem findAndMapVideos()).
        $mapped = $crawl->findAndMapVideos($listings, limit: 60, tryHoofootFallback: true);

        // Thumbnail lưu trên web server, không đẩy sang SX65 — ảnh nhỏ, nginx
        // serve trực tiếp rẻ hơn đi vòng qua CDN.
        Artisan::call('thumbnails:download');
        Artisan::call('logos:download');

        Log::info('CrawlMatchesJob: done', ['mapped' => $mapped]);
    }
}
