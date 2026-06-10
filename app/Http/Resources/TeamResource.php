<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->localized_name,
            'name_en'         => $this->name,
            'slug'            => $this->slug,
            'type'            => $this->type,
            'country'         => $this->country,
            'primary_color'   => $this->primary_color,
            'secondary_color' => $this->secondary_color,
            'logo_url'        => $this->logo_url,
            'initials'        => $this->initials,
        ];
    }
}
