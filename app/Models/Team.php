<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Team extends Model
{
    protected $fillable = [
        'name', 'slug', 'type', 'country',
        'logo_path', 'highlightly_id',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(TeamTranslation::class);
    }

    public function homeMatches(): HasMany
    {
        return $this->hasMany(FootballMatch::class, 'home_team_id');
    }

    public function awayMatches(): HasMany
    {
        return $this->hasMany(FootballMatch::class, 'away_team_id');
    }

    // Logo URL — handle cả external URL (API-Football) và local path
    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_path) return null;
        if (str_starts_with($this->logo_path, 'http')) {
            return $this->logo_path;
        }
        return Storage::url($this->logo_path);
    }

    // Tên theo locale — chỉ dịch national team
    public function getLocalizedNameAttribute(): string
    {
        if ($this->type === 'club') return $this->name;
        $locale = App::getLocale();
        if ($locale === 'en') return $this->name;
        $translation = $this->translations->firstWhere('locale', $locale);
        return $translation?->name ?? $this->name;
    }

    // Initials fallback khi không có logo
    public function getInitialsAttribute(): string
    {
        $words = explode(' ', $this->name);
        if (count($words) === 1) return strtoupper(substr($this->name, 0, 3));
        return strtoupper(implode('', array_map(fn($w) => $w[0], array_slice($words, 0, 3))));
    }

    public static function makeSlug(string $name): string
    {
        return Str::slug($name);
    }

    public static function findOrCreateByName(string $name): self
    {
        $slug = self::makeSlug($name);
        return self::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'slug' => $slug,
                'type' => self::guessType($name),
            ]
        );
    }

    public static function guessTypePublic(string $name): string
    {
        return self::guessType($name);
    }

    private static function guessType(string $name): string
    {
        $national = [
            'Afghanistan','Albania','Algeria','Andorra','Angola','Antigua and Barbuda',
            'Argentina','Armenia','Australia','Austria','Azerbaijan','Bahamas','Bahrain',
            'Bangladesh','Barbados','Belarus','Belgium','Belize','Benin','Bolivia',
            'Bosnia','Bosnia & Herzegovina','Botswana','Brazil','Bulgaria','Burkina Faso',
            'Burundi','Cambodia','Cameroon','Canada','Cape Verde','Cabo Verde',
            'Central African Republic','Chad','Chile','China','Colombia','Comoros',
            'Congo','Costa Rica','Croatia','Cuba','Czech Republic','Denmark','Djibouti',
            'Ecuador','Egypt','El Salvador','England','Equatorial Guinea','Eritrea',
            'Estonia','Eswatini','Ethiopia','Fiji','Finland','France','Gabon','Gambia',
            'Georgia','Germany','Ghana','Greece','Guatemala','Guinea','Guinea-Bissau',
            'Guyana','Haiti','Honduras','Hungary','Iceland','India','Indonesia','Iran',
            'Iraq','Ireland','Rep. Of Ireland','Israel','Italy','Ivory Coast','Jamaica',
            'Japan','Jordan','Kazakhstan','Kenya','Kosovo','Kuwait','Kyrgyzstan',
            'Latvia','Lebanon','Lesotho','Liberia','Libya','Liechtenstein','Lithuania',
            'Luxembourg','Madagascar','Malawi','Malaysia','Mali','Malta','Mauritania',
            'Mexico','Moldova','Montenegro','Morocco','Mozambique','Myanmar','Namibia',
            'Nepal','Netherlands','New Zealand','Nicaragua','Niger','Nigeria',
            'North Korea','North Macedonia','FYR Macedonia','Northern Ireland','Norway',
            'Oman','Pakistan','Panama','Paraguay','Peru','Philippines','Poland',
            'Portugal','Qatar','Romania','Russia','Rwanda','Saudi Arabia','Scotland',
            'Senegal','Serbia','Sierra Leone','Singapore','Slovakia','Slovenia',
            'Somalia','South Africa','South Korea','South Sudan','Spain','Sri Lanka',
            'Sudan','Suriname','Sweden','Switzerland','Syria','Tajikistan','Tanzania',
            'Thailand','Togo','Trinidad and Tobago','Tunisia','Turkmenistan',
            'Turkey','Türkiye','Uganda','Ukraine','UAE','United Arab Emirates',
            'Uruguay','USA','Uzbekistan','Venezuela','Vietnam','Wales','Yemen',
            'Zambia','Zimbabwe','Albania','Armenia','Georgia','Faroe Islands',
            'Gibraltar','Andorra','San Marino','Liechtenstein','Moldova','Kosovo',
            'Cyprus','Malta','Estonia','Latvia','Lithuania','Belarus','Azerbaijan',
            'Armenia','Kazakhstan','Israel','Algeria','Morocco','Tunisia','Egypt',
            'Senegal','Ghana','Nigeria','Cameroon','Ivory Coast','Mali','Burkina Faso',
        ];
        foreach ($national as $keyword) {
            if (strcasecmp($name, $keyword) === 0) return 'national';
        }
        return 'club';
    }
}
