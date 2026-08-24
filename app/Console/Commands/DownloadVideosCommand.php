<?php
namespace App\Console\Commands;

use App\Models\MatchVideo;
use App\Services\DownloadService;
use Illuminate\Console\Command;

class DownloadVideosCommand extends Command
{
    protected $signature   = 'download:videos';
    protected $description = 'Download HLS segments cho các match pending';

    public function handle(DownloadService $download): void
    {
        $pending = MatchVideo::where('status', 'pending')
            ->whereNotNull('embed_url')
            ->whereNull('local_path')
            ->count();

        $this->info("Pending videos: {$pending}");

        // Dùng chung downloadAllPending() với cron để không lệch logic:
        // nhận diện host → download → syncToStorage → markError khi hỏng.
        $downloaded = $download->downloadAllPending();

        $this->info("Downloaded: {$downloaded}");
        $this->info('Ready: ' . MatchVideo::where('status', 'ready')->count() . ' videos');
    }
}
