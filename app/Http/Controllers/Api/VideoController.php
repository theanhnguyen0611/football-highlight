<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MatchVideo;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class VideoController extends Controller
{
    public function stream(int $id): JsonResponse
    {
        $video = MatchVideo::findOrFail($id);

        if ($video->status !== 'ready') {
            return response()->json(['error' => 'Video not ready'], 404);
        }

        return response()->json([
            'stream_url'       => $video->stream_url,
            'duration_seconds' => $video->duration_seconds,
            'file_size_mb'     => $video->file_size_mb,
        ]);
    }

}
