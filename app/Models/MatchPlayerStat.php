<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchPlayerStat extends Model
{
    protected $fillable = [
        'match_id', 'team_id', 'api_player_id', 'name',
        'rating', 'minutes', 'goals', 'assists',
        'shots', 'passes', 'tackles', 'yellow_cards', 'red_cards',
    ];
    protected $casts = ['rating' => 'float'];

    public function match(): BelongsTo { return $this->belongsTo(FootballMatch::class, 'match_id'); }
    public function team(): BelongsTo  { return $this->belongsTo(Team::class); }
}
