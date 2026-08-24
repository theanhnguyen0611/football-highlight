<?php

namespace App\Console\Commands;

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
                            {--sync-only : Chỉ chạy Step 1 — nạp match/team/league, bỏ qua video}
                            {--map-limit=100 : Số match tối đa xét mỗi lượt map video}
                            {--details-limit=30 : Số match tối đa lấy venue/events mỗi lượt}';

    protected $description = 'Sync matches + highlights + videos in one optimized pass';

    public function handle(HighlightlyService $highlightly, CrawlService $crawl, DownloadService $download): void
    {
        $days         = (int) $this->option('days');
        $mapLimit     = (int) $this->option('map-limit');
        $detailsLimit = (int) $this->option('details-limit');

        $this->info("Step 1: Syncing matches & highlights from Highlightly ({$days} ngày)...");
        $t0 = microtime(true);
        $totalMatches = 0;

        for ($i = 0; $i < $days; $i++) {
            $date   = now()->subDays($i)->format('Y-m-d');
            $result = $highlightly->syncDate($date);
            $totalMatches += $result['matches'];

            // Backfill dài có thể chạy hàng giờ — in tiến độ và ETA để biết còn bao lâu
            $done = $i + 1;
            $eta  = (int) ((microtime(true) - $t0) / $done * ($days - $done));
            $this->line(sprintf(
                '  [%d/%d] %s: %d matches, %d thumbnails  (teams=%d, còn ~%dm)',
                $done, $days, $date,
                $result['matches'], $result['thumbnails'],
                Team::count(),
                (int) round($eta / 60)
            ));

            if ($i < $days - 1) sleep(1);
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
            return;
        }

        $this->info('Step 2: Syncing details for finished matches...');
        $detailed = $highlightly->syncFinishedMatchDetails(limit: $detailsLimit);
        $this->line("  Detailed: {$detailed} matches");

        $this->info('Step 3: Crawling Hoofoot listings...');
        $listings = $crawl->crawlHoofootListings();
        $this->line('  Found: ' . count($listings) . ' slugs');

        $this->info('Step 4: Find & map videos (Hoofoot chính, DasFootball backup)...');
        $mapped = $crawl->findAndMapVideos($listings, limit: $mapLimit, tryDasFootball: true);
        $this->line("  Mapped: {$mapped} videos");

        $this->info('Step 5: Download all pending...');
        $downloaded = $download->downloadAllPending();
        $this->line("  Downloaded: {$downloaded} videos");

        // Step 5 vừa đánh dấu 'error' cho các video Hoofoot tải hỏng, giờ mới
        // đủ điều kiện để findAndMapVideos() thử DasFootball cho đúng trận đó.
        $this->info('Step 5b: Fallback DasFootball cho trận Hoofoot hỏng...');
        $refallback = $crawl->findAndMapVideos($listings, limit: $mapLimit, tryDasFootball: true);
        if ($refallback > 0) {
            $this->line("  Mapped: {$refallback} videos");
            $downloaded = $download->downloadAllPending();
            $this->line("  Downloaded: {$downloaded} videos");
        } else {
            $this->line('  Không có trận nào cần fallback');
        }

        $this->info('Step 6: Download new logos...');
        $this->call('logos:download');

        $this->info('Step 6b: Download thumbnails (lưu trên web server)...');
        $this->call('thumbnails:download');

        $this->info('Step 7: Set league backgrounds...');
        $this->call('leagues:set-backgrounds');

        $this->info('Done!');
    }
}
