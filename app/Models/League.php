<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class League extends Model
{
    protected $fillable = [
        'name', 'slug', 'logo_path', 'background_url', 'primary_color', 'country', 'highlightly_id',
    ];

    protected $appends = ['background_url_full'];

    public function matches(): HasMany
    {
        return $this->hasMany(FootballMatch::class);
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_path) return null;
        if (str_starts_with($this->logo_path, 'http')) return $this->logo_path;
        // logo_path của giải self-host đã chứa sẵn "logos/leagues/..." — thêm
        // prefix "logos/" lần nữa sẽ ra "logos/logos/..." (path sai, ảnh vỡ).
        $path = str_starts_with($this->logo_path, 'logos/') ? $this->logo_path : 'logos/' . $this->logo_path;
        return Storage::url($path);
    }

    public function getBackgroundUrlFullAttribute(): ?string
    {
        if (!$this->background_url) return null;
        return asset('storage/' . $this->background_url);
    }
}
