<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MatchVideo;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class VideoController extends Controller
{
    public function stream(int $id): JsonResponse
    {
        $video = MatchVideo::findOrFail($id);

        if ($video->status !== 'ready') {
            return response()->json(['error' => 'Video not ready'], 404);
        }

        // Tạo signed URL hết hạn sau 1 giờ
        $signedUrl = URL::temporarySignedRoute(
            'hls.serve',
            now()->addHour(),
            ['path' => $video->local_path]
        );

        return response()->json([
            'stream_url'       => $signedUrl,
            'duration_seconds' => $video->duration_seconds,
            'file_size_mb'     => $video->file_size_mb,
        ]);
    }

    public function serve(string $path)
    {
        if (!request()->hasValidSignature()) {
            abort(403);
        }

        $fullPath = storage_path("app/public/{$path}");

        if (!file_exists($fullPath)) {
            abort(404);
        }

        $mimeType = str_ends_with($path, '.m3u8') ? 'application/vnd.apple.mpegurl' : 'video/mp2t';

        return response()->file($fullPath, [
            'Content-Type'                => $mimeType,
            'Access-Control-Allow-Origin' => '*',
        ]);
    }
}
