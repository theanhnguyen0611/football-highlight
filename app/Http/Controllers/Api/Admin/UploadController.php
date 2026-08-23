<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessFullMatchJob;
use App\Models\FootballMatch;
use App\Models\MatchVideo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UploadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'match_id'    => 'required|integer|exists:matches,id',
            'video_type'  => 'required|in:highlight,full_match',
            'youtube_url' => 'required_without:file|nullable|url',
            'file'        => 'required_without:youtube_url|nullable|file|mimes:mp4,mkv,avi,mov,webm|max:10485760',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $match     = FootballMatch::findOrFail($request->match_id);
        $videoType = $request->video_type;

        // Remove existing manual video of this type for the match
        MatchVideo::where('match_id', $match->id)
            ->where('video_type', $videoType)
            ->where('source', 'manual')
            ->delete();

        if ($request->hasFile('file')) {
            $file    = $request->file('file');
            $prefix  = $videoType === 'full_match' ? 'fullmatch_' : 'highlight_';
            $tmpPath = storage_path('app/tmp/' . uniqid($prefix) . '.' . $file->getClientOriginalExtension());
            if (!is_dir(dirname($tmpPath))) mkdir(dirname($tmpPath), 0755, true);
            $file->move(dirname($tmpPath), basename($tmpPath));

            $video = MatchVideo::create([
                'match_id'   => $match->id,
                'source'     => 'manual',
                'video_type' => $videoType,
                'status'     => 'pending',
            ]);

            ProcessFullMatchJob::dispatch($video->id, $tmpPath);

            return response()->json([
                'message'  => 'File uploaded — converting to HLS in background',
                'video_id' => $video->id,
                'match'    => $match->slug,
            ]);
        }

        $video = MatchVideo::create([
            'match_id'   => $match->id,
            'source'     => 'manual',
            'video_type' => $videoType,
            'source_url' => $request->youtube_url,
            'status'     => 'pending',
        ]);

        ProcessFullMatchJob::dispatch($video->id);

        return response()->json([
            'message'  => 'YouTube URL queued for download',
            'video_id' => $video->id,
            'match'    => $match->slug,
        ]);
    }

    public function show(Request $request, int $matchId): JsonResponse
    {
        $type = $request->query('type', 'full_match');

        $video = MatchVideo::where('match_id', $matchId)
            ->where('video_type', $type)
            ->latest()
            ->first();

        if (!$video) {
            return response()->json(['status' => 'none']);
        }

        return response()->json([
            'status'           => $video->status,
            'video_id'         => $video->id,
            'source'           => $video->source,
            'source_url'       => $video->source_url,
            'duration_seconds' => $video->duration_seconds,
            'file_size_mb'     => $video->file_size_mb,
            'local_path'       => $video->local_path,
        ]);
    }
}
