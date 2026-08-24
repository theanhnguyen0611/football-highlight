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

        $data = [
            'stream_url'       => $video->stream_url,
            'duration_seconds' => $video->duration_seconds,
            'file_size_mb'     => $video->file_size_mb,
        ];

        $tokenKey = config('services.cdn.token_key');
        if ($tokenKey && $video->local_path) {
            $expires   = time() + 14400; // 4 hours
            $tokenPath = '/bolareel/' . dirname($video->local_path) . '/';
            $hash      = md5($tokenKey . $tokenPath . $expires, true);
            $token     = rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');

            $data['cdn_token']      = $token;
            $data['cdn_expires']    = $expires;
            $data['cdn_token_path'] = $tokenPath;
        }

        return response()->json($data);
    }

}
