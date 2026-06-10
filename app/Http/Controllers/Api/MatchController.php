<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MatchCollection;
use App\Http\Resources\MatchResource;
use App\Models\FootballMatch;
use Illuminate\Http\Request;

class MatchController extends Controller
{
    public function index(Request $request): MatchCollection
    {
        $query = FootballMatch::with(['homeTeam.translations', 'awayTeam.translations', 'videos']);

        if ($request->filled('league')) {
            $query->where('league_slug', $request->league);
        }
        if ($request->filled('team')) {
            $team = $request->team;
            $query->whereHas('homeTeam', fn($q) => $q->where('slug', $team))
                  ->orWhereHas('awayTeam', fn($q) => $q->where('slug', $team));
        }
        if ($request->filled('date')) {
            $query->whereDate('match_date', $request->date);
        }
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($query) use ($q) {
                $query->where('title', 'like', "%{$q}%")
                      ->orWhereHas('homeTeam', fn($sq) => $sq->where('name', 'like', "%{$q}%"))
                      ->orWhereHas('awayTeam', fn($sq) => $sq->where('name', 'like', "%{$q}%"));
            });
        }

        $matches = $query->orderBy('match_date', 'desc')
                         ->paginate($request->integer('per_page', 20));

        return new MatchCollection($matches);
    }

    public function show(string $slug): MatchResource
    {
        $match = FootballMatch::with(['homeTeam.translations', 'awayTeam.translations', 'videos'])
            ->where('slug', $slug)
            ->firstOrFail();

        return new MatchResource($match);
    }
}
