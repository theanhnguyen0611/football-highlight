<?php

namespace App\Jobs;

use App\Models\FootballMatch;
use App\Models\MatchVideo;
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

        $videos = MatchVideo::with('match')
            ->where('status', 'pending')
            ->get();

        Log::info('DownloadVideosJob: pending', ['count' => $videos->count()]);

        foreach ($videos as $video) {
            try {
                $video->markDownloading();

                $hlsUrl = $download->getHlsUrl($video->embed_url);
                if (!$hlsUrl) {
                    Log::warning('DownloadVideosJob: no HLS', ['video_id' => $video->id]);
                    $video->markError();
                    continue;
                }

                $bestUrl = $download->getBestQualityUrl($hlsUrl);
                $ok      = $download->downloadHls($video, $bestUrl);

                if (!$ok) {
                    $video->markError();
                    continue;
                }

                $match       = $video->match;
                $total       = $match->videos()->count();
                $ready       = $match->videos()->where('status', 'ready')->count();

                if ($total > 0 && $total === $ready) {
                    $match->markReady();
                }

            } catch (\Exception $e) {
                Log::error('DownloadVideosJob: error', [
                    'video_id' => $video->id,
                    'error'    => $e->getMessage(),
                ]);
                $video->markError();
            }

            sleep(1);
        }

        Log::info('DownloadVideosJob: done');
    }
}
