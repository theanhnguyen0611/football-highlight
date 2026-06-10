<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VideoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'video_type'       => $this->video_type,
            'language'         => $this->language,
            'status'           => $this->status,
            'file_size_mb'     => $this->file_size_mb,
            'duration_seconds' => $this->duration_seconds,
            'stream_url'       => $this->status === 'ready'
                ? route('api.videos.stream', $this->id)
                : null,
        ];
    }
}
