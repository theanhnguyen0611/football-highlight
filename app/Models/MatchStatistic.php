<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchStatistic extends Model
{
    protected $fillable = ['match_id', 'team_id', 'stats'];
    protected $casts    = ['stats' => 'array'];

    public function match(): BelongsTo { return $this->belongsTo(FootballMatch::class, 'match_id'); }
    public function team(): BelongsTo  { return $this->belongsTo(Team::class); }
}
