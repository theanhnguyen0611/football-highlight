<?php

namespace App\Jobs;

use App\Models\MatchVideo;
use App\Services\DownloadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessFullMatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 7200;
    public int $tries   = 1;

    public function __construct(
        public int $videoId,
        public ?string $uploadedFilePath = null
    ) {}

    public function handle(DownloadService $download): void
    {
        $video = MatchVideo::with('match')->findOrFail($this->videoId);
        $video->markDownloading();

        try {
            $ok = $this->uploadedFilePath
                ? $download->downloadFullMatchFromFile($video, $this->uploadedFilePath)
                : $download->downloadYoutube($video, $video->source_url);

            if (!$ok) {
                $video->markError();
            }
        } catch (\Exception $e) {
            Log::error('ProcessFullMatchJob failed', ['video_id' => $this->videoId, 'error' => $e->getMessage()]);
            $video->markError();
        }
    }
}
