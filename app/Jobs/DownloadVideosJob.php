<?php

namespace App\Jobs;

use App\Services\DownloadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DownloadVideosJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3000;
    public int $tries   = 2;

    // Cron đẩy job dày hơn thời gian chạy → chặn chồng lượt.
    // Khoá hết hạn cùng lúc timeout để job treo không kẹt mãi.
    public int $uniqueFor = 3000;

    public function handle(DownloadService $download): void
    {
        Log::info('DownloadVideosJob: start');
        $downloaded = $download->downloadAllPending();
        Log::info('DownloadVideosJob: done', ['downloaded' => $downloaded]);
    }
}
