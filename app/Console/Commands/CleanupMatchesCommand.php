<?php

namespace App\Console\Commands;

use App\Models\FootballMatch;
use App\Models\MatchVideo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupMatchesCommand extends Command
{
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

    private function deleteFiles(FootballMatch $match): int
    {
        $count = 0;

        // HLS video directory: storage/app/public/videos/{match_id}/
        $videoDir = "public/videos/{$match->id}";
        if (Storage::exists($videoDir)) {
            Storage::deleteDirectory($videoDir);
            $count++;
        }

        // Thumbnail: storage/public/thumbnails/{slug}.jpg
        $thumbPath = public_path("storage/thumbnails/{$match->slug}.jpg");
        if (file_exists($thumbPath)) {
            unlink($thumbPath);
            $count++;
        }

        return $count;
    }
}
