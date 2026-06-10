<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class MatchCollection extends ResourceCollection
{
    public $collects = MatchResource::class;
}
