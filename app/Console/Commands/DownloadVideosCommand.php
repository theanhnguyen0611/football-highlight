<?php
namespace App\Console\Commands;

use App\Models\FootballMatch;
use App\Models\MatchVideo;
use App\Services\DownloadService;
use Illuminate\Console\Command;

class DownloadVideosCommand extends Command
{
    protected $signature   = 'download:videos';
    protected $description = 'Download HLS segments cho các match pending';

    public function handle(DownloadService $download): void
    {
        $videos = MatchVideo::with('match.homeTeam', 'match.awayTeam')
            ->where('status', 'pending')
            ->get();

        $this->info("Pending videos: " . $videos->count());

        foreach ($videos as $video) {
            $slug = $video->match->slug;
            $this->line("\n  [{$video->id}] {$slug}");

            try {
                $video->markDownloading();

                $hlsUrl = $download->getHlsUrl($video->embed_url);
                if (!$hlsUrl) {
                    $this->warn("  No HLS found");
                    $video->markError();
                    continue;
                }

                $ok = $download->downloadHls($video, $hlsUrl);
                if (!$ok) {
                    $this->warn("  Download failed");
                    $video->markError();
                    continue;
                }

                $video->refresh();
                $this->info("  Done: {$video->file_size_mb} MB, {$video->duration_seconds}s");

                $match = $video->match;
                $total = $match->videos()->count();
                $ready = $match->videos()->where('status', 'ready')->count();
                if ($total > 0 && $total === $ready) {
                    $match->markReady();
                    $this->info("  Match ready: {$slug}");
                }
            } catch (\Exception $e) {
                $this->error("  Error: " . $e->getMessage());
                $video->markError();
            }

            sleep(1);
        }

        $this->info("\nDone! Ready: " . MatchVideo::where('status', 'ready')->count() . " videos");
    }
}
