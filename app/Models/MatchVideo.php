<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchVideo extends Model
{
    protected $fillable = [
        'match_id', 'source', 'source_url', 'local_path',
        'quality', 'language', 'status', 'duration_seconds', 'file_size_mb',
    ];

    public function match(): BelongsTo
    {
        return $this->belongsTo(FootballMatch::class, 'match_id');
    }

    public function getStreamUrlAttribute(): ?string
    {
        if ($this->local_path) {
            return route('api.hls', ['path' => $this->local_path]);
        }
        return $this->source_url;
    }

    public function isReady(): bool
    {
        return $this->status === 'ready' && $this->local_path;
    }
}
