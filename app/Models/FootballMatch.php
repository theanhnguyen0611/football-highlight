<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FootballMatch extends Model
{
    protected $table = 'matches';

    protected $fillable = [
        'slug', 'home_team_id', 'away_team_id', 'league_id',
        'home_score', 'away_score', 'score_penalties', 'match_date', 'kick_off_time',
        'venue', 'referee', 'round', 'thumbnail_url', 'status', 'match_status',
        'highlightly_id', 'statistics', 'details_synced_at',
    ];

    protected $casts = [
        'match_date'        => 'date',
        'statistics'        => 'array',
        'details_synced_at' => 'datetime',
    ];

    // Cột thumbnail_url giữ giá trị thô: URL http từ Highlightly khi mới sync, đổi
    // thành tên file sau khi thumbnails:download tải về web server. Giống cách
    // logo_path/logo_url hoạt động ở Team và League.
    public function getThumbnailUrlAttribute(): ?string
    {
        $raw = $this->attributes['thumbnail_url'] ?? null;
        if (!$raw) return null;
        if (str_starts_with($raw, 'http')) return $raw;

        return Storage::url('thumbnails/' . $raw);
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(MatchVideo::class, 'match_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(MatchEvent::class, 'match_id');
    }

    public static function makeSlug(string $home, string $away, string $date): string
    {
        return Str::slug("{$home}-vs-{$away}-{$date}");
    }
}
