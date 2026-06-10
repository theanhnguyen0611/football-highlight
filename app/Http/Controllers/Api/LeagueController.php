<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FootballMatch;
use Illuminate\Http\JsonResponse;

class LeagueController extends Controller
{
    public function index(): JsonResponse
    {
        $leagues = FootballMatch::query()
            ->select('league')
            ->selectRaw('COUNT(*) as match_count')
            ->whereNotNull('league')
            ->groupBy('league')
            ->orderByDesc('match_count')
            ->get();

        return response()->json(['data' => $leagues]);
    }
}
