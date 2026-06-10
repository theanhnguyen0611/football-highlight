<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MatchCollection;
use App\Http\Resources\TeamResource;
use App\Models\FootballMatch;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index(): JsonResponse
    {
        $teams = Team::withCount([
                'homeMatches as home_count',
                'awayMatches as away_count',
            ])
            ->get()
            ->map(fn($t) => [
                ...(new TeamResource($t))->resolve(),
                'match_count' => $t->home_count + $t->away_count,
            ])
            ->sortByDesc('match_count')
            ->values();

        return response()->json(['data' => $teams]);
    }

    public function show(string $slug): TeamResource
    {
        return new TeamResource(Team::where('slug', $slug)->firstOrFail());
    }

    public function matches(string $slug, Request $request): MatchCollection
    {
        $team    = Team::where('slug', $slug)->firstOrFail();
        $matches = FootballMatch::with(['homeTeam.translations', 'awayTeam.translations', 'videos'])
            ->where('home_team_id', $team->id)
            ->orWhere('away_team_id', $team->id)
            ->orderBy('match_date', 'desc')
            ->paginate($request->integer('per_page', 20));

        return new MatchCollection($matches);
    }
}
