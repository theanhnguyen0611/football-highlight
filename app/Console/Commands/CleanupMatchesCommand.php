<?php

namespace App\Console\Commands;

use App\Models\FootballMatch;
use App\Models\MatchVideo;
use App\Services\DownloadService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CleanupMatchesCommand extends Command
{
    public function __construct(private DownloadService $downloader)
    {
        parent::__construct();
    }

    protected $signature = 'matches:cleanup
                            {--days=14 : Delete matches older than this many days with no ready video}
                            {--prune-hoofoot : Remove Hoofoot records on matches that already have a ready DasFootball video}
                            {--dry-run : Preview what would be deleted without actually deleting}';

    protected $description = 'Delete old matches that have no ready video, and remove their local files';

    public function handle(): void
    {
        $days   = (int) $this->option('days');
        $dryRun = (bool) $this->option('dry-run');
        $cutoff = now()->subDays($days)->startOfDay();

        if ($dryRun) {
            $this->warn('[DRY RUN] No changes will be made.');
        }

        if ($this->option('prune-hoofoot')) {
            $this->pruneHoofoot($dryRun);
        }

        // Lọc bằng SQL chứ không ->get()->filter(): sau backfill lịch sử, bảng
        // matches có thể tới hàng chục nghìn dòng và nạp hết vào RAM sẽ chết.
        $query = FootballMatch::where('match_date', '<', $cutoff)
            ->whereDoesntHave('videos', fn ($q) => $q->where('status', 'ready'));

        $total = $query->count();
        $this->info("Found {$total} matches older than {$days} days with no ready video.");

        if ($total === 0) {
            $this->info('Nothing to clean up.');
            return;
        }

        if ($dryRun) {
            $query->clone()->orderBy('match_date')->limit(20)->get()
                ->each(fn ($m) => $this->line("  [{$m->match_date->format('Y-m-d')}] {$m->slug}"));
            if ($total > 20) $this->line('  ... và ' . ($total - 20) . ' trận nữa');
            $this->warn("Would delete {$total} matches (bỏ --dry-run để thực hiện).");
            return;
        }

        $deletedMatches = 0;
        $deletedFiles   = 0;
        $bar = $this->output->createProgressBar($total);

        $query->with('videos')->chunkById(200, function ($chunk) use (&$deletedMatches, &$deletedFiles, $bar) {
            foreach ($chunk as $match) {
                $deletedFiles += $this->deleteFiles($match);
                $match->videos()->delete();
                $match->events()->delete();
                $match->delete();

                $deletedMatches++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Deleted {$deletedMatches} matches, removed {$deletedFiles} file(s).");
        $this->comment('Teams và leagues KHÔNG bị xoá — dữ liệu backfill được giữ nguyên.');
    }

    private function pruneHoofoot(bool $dryRun): void
    {
        // Match IDs that have at least one DasFootball video with status=ready
        // (DasFootball là nguồn chính từ giờ — xem findAndMapVideos())
        $dasFbMatchIds = MatchVideo::where('source', 'dasfootball')
            ->where('status', 'ready')
            ->pluck('match_id')
            ->unique();

        // Hoofoot records on those same matches
        $toDelete = MatchVideo::where('source', 'hoofoot')
            ->whereIn('match_id', $dasFbMatchIds)
            ->get();

        $this->info("Found {$toDelete->count()} Hoofoot record(s) superseded by DasFootball.");

        if ($toDelete->isEmpty()) {
            return;
        }

        foreach ($toDelete as $v) {
            $this->line("  video #{$v->id} match_id={$v->match_id} status={$v->status}");
        }

        if (!$dryRun) {
            MatchVideo::where('source', 'hoofoot')
                ->whereIn('match_id', $dasFbMatchIds)
                ->delete();
            $this->info("Deleted {$toDelete->count()} Hoofoot record(s).");
        } else {
            $this->warn("Would delete {$toDelete->count()} Hoofoot record(s) (use without --dry-run to apply).");
        }
    }

    // Video được ghi trên CX23 rồi mới rsync sang SX65, và syncToStorage() xoá bản
    // local sau khi rsync thành công. Nên phải dọn cả hai nơi: local còn sót khi
    // rsync hỏng, còn SX65 giữ bản chính.
    private function deleteFiles(FootballMatch $match): int
    {
        $count = 0;
        $slug  = $match->slug;

        // syncToStorage() chỉ chạy sau khi tải xong, nên trận chưa từng có video
        // ready thì trên SX65 không có gì. Không kiểm tra điều này thì mỗi trận
        // tốn 2 lần ssh — dọn 10k trận sau backfill là 20k round-trip vô ích.
        $everSynced = $match->videos->contains(fn ($v) => !empty($v->local_path));

        foreach (["highlights/{$slug}", "full-matches/{$slug}"] as $relDir) {
            $localDir = storage_path("app/public/{$relDir}");
            if (is_dir($localDir)) {
                File::deleteDirectory($localDir);
                $count++;
            }

            if ($everSynced && $this->downloader->deleteFromStorage($relDir)) {
                $count++;
            }
        }

        $thumbPath = storage_path("app/public/thumbnails/{$slug}.jpg");
        if (file_exists($thumbPath)) {
            unlink($thumbPath);
            $count++;
        }

        return $count;
    }
}
