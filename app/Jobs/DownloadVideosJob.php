<?php

namespace App\Jobs;

use App\Services\DownloadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DownloadVideosJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;
    public int $tries   = 2;

    public function handle(DownloadService $download): void
    {
        Log::info('DownloadVideosJob: start');
        $downloaded = $download->downloadAllPending();
        Log::info('DownloadVideosJob: done', ['downloaded' => $downloaded]);
    }
}
