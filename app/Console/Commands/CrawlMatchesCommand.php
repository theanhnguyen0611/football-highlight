<?php

namespace App\Console\Commands;

use App\Exceptions\HighlightlyQuotaException;
use App\Models\League;
use App\Models\Team;
use App\Services\CrawlService;
use App\Services\DownloadService;
use App\Services\HighlightlyService;
use Illuminate\Console\Command;

class CrawlMatchesCommand extends Command
{
    protected $signature = 'crawl:matches
                            {--days=3 : Số ngày lịch sử cần sync}
                            {--offset=0 : Bắt đầu từ N ngày trước (để chạy tiếp backfill dở)}
                            {--min-quota=100 : Dừng khi quota Highlightly còn dưới mức này}
                            {--sync-only : Chỉ chạy Step 1 — nạp match/team/league, bỏ qua video}
                            {--map-limit=100 : Số match tối đa xét mỗi lượt map video}
                            {--no-dasfootball : Chỉ map Hoofoot — bỏ qua Playwright, nhanh hơn nhiều}
                            {--details-limit=30 : Số match tối đa lấy venue/events mỗi lượt}';

    protected $description = 'Sync matches + highlights + videos in one optimized pass';

    public function handle(HighlightlyService $highlightly, CrawlService $crawl, DownloadService $download): int
    {
        $days         = (int) $this->option('days');
        $offset       = (int) $this->option('offset');
        $mapLimit     = (int) $this->option('map-limit');
        $detailsLimit = (int) $this->option('details-limit');

        $highlightly->quotaFloor = (int) $this->option('min-quota');

        $this->info("Step 1: Syncing matches & highlights from Highlightly ({$days} ngày, offset {$offset})...");
        $t0 = microtime(true);
        $totalMatches = 0;
        $i = 0;

        try {
            for ($i = 0; $i < $days; $i++) {
                $date   = now()->subDays($offset + $i)->format('Y-m-d');
                $result = $highlightly->syncDate($date);
                $totalMatches += $result['matches'];

                // Backfill dài có thể chạy hàng giờ — in tiến độ và ETA để biết còn bao lâu
                $done = $i + 1;
                $eta  = (int) ((microtime(true) - $t0) / $done * ($days - $done));
                $this->line(sprintf(
                    '  [%d/%d] %s: %d matches, %d thumbnails  (teams=%d, quota=%s, còn ~%dm)',
                    $done, $days, $date,
                    $result['matches'], $result['thumbnails'],
                    Team::count(),
                    $highlightly->quotaRemaining ?? '?',
                    (int) round($eta / 60)
                ));

                if ($i < $days - 1) sleep(1);
            }
        } catch (HighlightlyQuotaException $e) {
            $this->newLine();
            $this->error($e->getMessage());
            $this->warn(sprintf('Đã xong %d/%d ngày (tới %s).', $i, $days, now()->subDays($offset + $i)->format('Y-m-d')));
            $this->warn('Quota reset theo ngày. Mai chạy tiếp bằng:');
            $this->line(sprintf(
                '  php artisan crawl:matches --days=%d --offset=%d%s',
                $days - $i,
                $offset + $i,
                $this->option('sync-only') ? ' --sync-only' : ''
            ));
            return self::FAILURE;
        }

        $this->info(sprintf(
            '  Step 1 xong: %d match, %d team, %d league trong %dm',
            $totalMatches,
            Team::count(),
            League::count(),
            (int) round((microtime(true) - $t0) / 60)
        ));

        // Backfill lịch sử chỉ cần team/league — Hoofoot không giữ video trận cũ
        // nên map + download ở đó gần như vô ích và rất tốn thời gian.
        if ($this->option('sync-only')) {
            $this->comment('--sync-only: dừng ở đây, chưa map/tải video.');
            return self::SUCCESS;
        }

        $this->info('Step 2: Syncing details for finished matches...');
        $detailed = $highlightly->syncFinishedMatchDetails(limit: $detailsLimit);
        $this->line("  Detailed: {$detailed} matches");

        $this->info('Step 3: Crawling Hoofoot listings...');
        $listings = $crawl->crawlHoofootListings();
        $this->line('  Found: ' . count($listings) . ' slugs');

        // DasFootball dùng Playwright, ~5-7s mỗi match không khớp Hoofoot. Sau
        // backfill lịch sử, số ứng viên lên hàng nghìn nên vòng thu hoạch đầu
        // nên tắt nó đi; cron sẽ lo phần fallback trên tập nhỏ còn lại.
        $tryDas = !$this->option('no-dasfootball');

        $this->info('Step 4: Find & map videos (Hoofoot chính' . ($tryDas ? ', DasFootball backup' : ', BỎ DasFootball') . ')...');
        $mapped = $crawl->findAndMapVideos($listings, limit: $mapLimit, tryDasFootball: $tryDas);
        $this->line("  Mapped: {$mapped} videos");

        $this->info('Step 5: Download all pending...');
        $downloaded = $download->downloadAllPending();
        $this->line("  Downloaded: {$downloaded} videos");

        // Step 5 vừa đánh dấu 'error' cho các video Hoofoot tải hỏng, giờ mới
        // đủ điều kiện để findAndMapVideos() thử DasFootball cho đúng trận đó.
        if (!$tryDas) {
            $this->comment('Step 5b: bỏ qua (--no-dasfootball)');
        } else {
            $this->info('Step 5b: Fallback DasFootball cho trận Hoofoot hỏng...');
            $refallback = $crawl->findAndMapVideos($listings, limit: $mapLimit, tryDasFootball: true);

            if ($refallback > 0) {
                $this->line("  Mapped: {$refallback} videos");
                $downloaded = $download->downloadAllPending();
                $this->line("  Downloaded: {$downloaded} videos");
            } else {
                $this->line('  Không có trận nào cần fallback');
            }
        }

        $this->info('Step 6: Download new logos...');
        $this->call('logos:download');

        $this->info('Step 6b: Download thumbnails (lưu trên web server)...');
        $this->call('thumbnails:download');

        $this->info('Step 7: Set league backgrounds...');
        $this->call('leagues:set-backgrounds');

        $this->info('Done!');

        return self::SUCCESS;
    }
}
