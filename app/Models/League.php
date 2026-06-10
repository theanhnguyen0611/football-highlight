<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class League extends Model
{
    protected $fillable = [
        'name', 'slug', 'logo_path', 'country', 'highlightly_id',
    ];

    public function matches(): HasMany
    {
        return $this->hasMany(FootballMatch::class);
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_path) return null;
        if (str_starts_with($this->logo_path, 'http')) return $this->logo_path;
        return asset('storage/' . $this->logo_path);
    }
}
