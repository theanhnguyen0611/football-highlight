<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchVideo extends Model
{
    protected $fillable = [
        'match_id', 'source', 'video_type', 'source_url', 'embed_url', 'local_path',
        'quality', 'language', 'status', 'duration_seconds', 'file_size_mb',
    ];

    public function match(): BelongsTo
    {
        return $this->belongsTo(FootballMatch::class, 'match_id');
    }

    public function getStreamUrlAttribute(): ?string
    {
        if ($this->local_path) {
            $cdnUrl = config('services.cdn.url');
            if ($cdnUrl) {
                return rtrim($cdnUrl, '/') . '/bolareel/' . $this->local_path;
            }
            return route('api.hls', ['path' => $this->local_path]);
        }
        return $this->embed_url ?? $this->source_url;
    }

    public function isReady(): bool
    {
        return $this->status === 'ready' && $this->local_path;
    }

    public function markReady(string $localPath, float $sizeMb, int $duration): void
    {
        $this->update([
            'local_path'       => $localPath,
            'status'           => 'ready',
            'file_size_mb'     => $sizeMb,
            'duration_seconds' => $duration,
        ]);
    }

    public function markDownloading(): void
    {
        $this->update(['status' => 'downloading']);
    }

    public function markError(): void
    {
        $this->update(['status' => 'error']);
    }
}
