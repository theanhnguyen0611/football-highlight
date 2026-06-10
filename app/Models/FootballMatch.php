<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class FootballMatch extends Model
{
    protected $table = 'matches';

    protected $fillable = [
        'slug', 'home_team_id', 'away_team_id', 'league_id',
        'home_score', 'away_score', 'match_date', 'kick_off_time',
        'venue', 'round', 'thumbnail_url', 'status',
        'highlightly_id',
    ];

    protected $casts = [
        'match_date' => 'date',
    ];

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
