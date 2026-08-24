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
                            {--prune-dasfootball : Remove DasFootball records on matches that already have a ready Hoofoot video}
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

        if ($this->option('prune-dasfootball')) {
            $this->pruneDasFootball($dryRun);
        }

        $matches = FootballMatch::with('videos')
            ->where('match_date', '<', $cutoff)
            ->get()
            ->filter(fn ($m) => $m->videos->where('status', 'ready')->isEmpty());

        $this->info("Found {$matches->count()} matches older than {$days} days with no ready video.");

        if ($matches->isEmpty()) {
            $this->info('Nothing to clean up.');
            return;
        }

        $deletedMatches = 0;
        $deletedFiles   = 0;

        foreach ($matches as $match) {
            $label = "[{$match->match_date->format('Y-m-d')}] {$match->slug} (ID: {$match->id})";
            $this->line("  {$label}");

            if (!$dryRun) {
                $deletedFiles += $this->deleteFiles($match);
                $match->videos()->delete();
                $match->events()->delete();
                $match->delete();
            }

            $deletedMatches++;
        }

        if ($dryRun) {
            $this->warn("Would delete {$deletedMatches} matches (use without --dry-run to apply).");
        } else {
            $this->info("Deleted {$deletedMatches} matches, removed {$deletedFiles} file(s).");
        }
    }

    private function pruneDasFootball(bool $dryRun): void
    {
        // Match IDs that have at least one Hoofoot video with status=ready
        $hoofootMatchIds = MatchVideo::where('source', 'hoofoot')
            ->where('status', 'ready')
            ->pluck('match_id')
            ->unique();

        // DasFootball records on those same matches
        $toDelete = MatchVideo::where('source', 'dasfootball')
            ->whereIn('match_id', $hoofootMatchIds)
            ->get();

        $this->info("Found {$toDelete->count()} DasFootball record(s) superseded by Hoofoot.");

        if ($toDelete->isEmpty()) {
            return;
        }

        foreach ($toDelete as $v) {
            $this->line("  video #{$v->id} match_id={$v->match_id} status={$v->status}");
        }

        if (!$dryRun) {
            MatchVideo::where('source', 'dasfootball')
                ->whereIn('match_id', $hoofootMatchIds)
                ->delete();
            $this->info("Deleted {$toDelete->count()} DasFootball record(s).");
        } else {
            $this->warn("Would delete {$toDelete->count()} DasFootball record(s) (use without --dry-run to apply).");
        }
    }

    // Video được ghi trên CX23 rồi mới rsync sang SX65, và syncToStorage() xoá bản
    // local sau khi rsync thành công. Nên phải dọn cả hai nơi: local còn sót khi
    // rsync hỏng, còn SX65 giữ bản chính.
    private function deleteFiles(FootballMatch $match): int
    {
        $count = 0;
        $slug  = $match->slug;

        foreach (["highlights/{$slug}", "full-matches/{$slug}"] as $relDir) {
            $localDir = storage_path("app/public/{$relDir}");
            if (is_dir($localDir)) {
                File::deleteDirectory($localDir);
                $this->line("      local: {$relDir}");
                $count++;
            }

            if ($this->downloader->deleteFromStorage($relDir)) {
                $this->line("      sx65 : {$relDir}");
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
