<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\CrawlMatchesJob;
use App\Jobs\DownloadVideosJob;
use App\Models\FootballMatch;
use App\Models\MatchVideo;
use App\Services\CrawlService;
use App\Services\DownloadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrawlController extends Controller
{
    public function crawl(): JsonResponse
    {
        CrawlMatchesJob::dispatch();
        return response()->json(['message' => 'Crawl job dispatched']);
    }

    public function download(int $matchId): JsonResponse
    {
        $match = FootballMatch::with('videos')->findOrFail($matchId);

        // Reset videos về pending để download lại
        $match->videos()->update(['status' => 'pending']);

        DownloadVideosJob::dispatch();

        return response()->json(['message' => "Download job dispatched for {$match->slug}"]);
    }

    public function retryErrors(): JsonResponse
    {
        $count = MatchVideo::where('status', 'error')->update(['status' => 'pending']);
        DownloadVideosJob::dispatch();

        return response()->json(['message' => "{$count} videos reset to pending"]);
    }
}
