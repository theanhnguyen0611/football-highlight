<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamTranslation extends Model
{
    protected $fillable = ['team_id', 'locale', 'name'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
