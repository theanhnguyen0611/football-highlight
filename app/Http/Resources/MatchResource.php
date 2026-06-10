<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'slug'         => $this->slug,
            'home_team'    => [
                'id'           => $this->homeTeam->id,
                'name'         => $this->homeTeam->name,
                'slug'         => $this->homeTeam->slug,
                'type'         => $this->homeTeam->type,
                'logo_url'     => $this->homeTeam->logo_url,
                'initials'     => $this->homeTeam->initials,
                'translations' => $this->homeTeam->translations->map(fn($t) => [
                    'locale' => $t->locale,
                    'name'   => $t->name,
                ])->toArray(),
            ],
            'away_team'    => [
                'id'           => $this->awayTeam->id,
                'name'         => $this->awayTeam->name,
                'slug'         => $this->awayTeam->slug,
                'type'         => $this->awayTeam->type,
                'logo_url'     => $this->awayTeam->logo_url,
                'initials'     => $this->awayTeam->initials,
                'translations' => $this->awayTeam->translations->map(fn($t) => [
                    'locale' => $t->locale,
                    'name'   => $t->name,
                ])->toArray(),
            ],
            'league'       => $this->league ? [
                'id'       => $this->league->id,
                'name'     => $this->league->name,
                'slug'     => $this->league->slug,
                'logo_url' => $this->league->logo_url,
            ] : null,
            'home_score'   => $this->home_score,
            'away_score'   => $this->away_score,
            'match_date'   => $this->match_date,
            'kick_off_time'=> $this->kick_off_time,
            'venue'        => $this->venue,
            'round'        => $this->round,
            'thumbnail_url'=> $this->thumbnail_url,
            'status'       => $this->status,
            'videos'       => $this->videos->map(fn($v) => [
                'id'         => $v->id,
                'source'     => $v->source,
                'source_url' => $v->source_url,
                'local_path' => $v->local_path,
                'status'     => $v->status,
                'language'   => $v->language,
            ])->toArray(),
        ];
    }
}
