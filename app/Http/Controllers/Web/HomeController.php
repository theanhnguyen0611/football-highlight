<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\FootballMatch;
use App\Models\League;
use App\Models\Team;
use App\Models\MatchEvent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        $query = FootballMatch::with([
            'homeTeam',
            'awayTeam',
            'league',
            'videos',
        ])->orderBy('match_date', 'desc');

        // Search
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sq) use ($q) {
                $sq->whereHas('homeTeam', fn($t) => $t->where('name', 'like', "%{$q}%"))
                   ->orWhereHas('awayTeam', fn($t) => $t->where('name', 'like', "%{$q}%"));
            });
        }

        // Filter by league slug
        if ($request->filled('league')) {
            $query->whereHas('league', fn($l) => $l->where('slug', $request->league));
        }

        $matches = $query->paginate(20)->withQueryString();

        // Leagues sidebar
        $leagues = League::withCount('matches')
            ->having('matches_count', '>', 0)
            ->orderByDesc('matches_count')
            ->get()
            ->map(fn($l) => [
                'id'           => $l->id,
                'name'         => $l->name,
                'slug'         => $l->slug,
                'logo_url'     => $l->logo_url,
                'league_slug'  => $l->slug,
                'match_count'  => $l->matches_count,
            ]);

        // Popular teams
        $popularTeams = Team::withCount([
                'homeMatches as home_count',
                'awayMatches as away_count',
            ])
            ->get()
            ->map(fn($t) => [
                'id'          => $t->id,
                'name'        => $t->name,
                'slug'        => $t->slug,
                'type'        => $t->type,
                'logo_url'    => $t->logo_url,
                'initials'    => $t->initials,
                'match_count' => $t->home_count + $t->away_count,
                'translations'=> $t->translations->map(fn($tr) => [
                    'locale' => $tr->locale,
                    'name'   => $tr->name,
                ])->toArray(),
            ])
            ->sortByDesc('match_count')
            ->take(12)
            ->values();

        return Inertia::render('Home', [
            'matches'       => $matches,
            'leagues'       => $leagues,
            'popular_teams' => $popularTeams,
            'filters'       => $request->only(['q', 'league']),
            'locale'        => app()->getLocale(),
        ]);
    }

    public function show(string $slug): Response
    {
        $match = FootballMatch::with([
            'homeTeam',
            'awayTeam',
            'league',
            'videos',
            'events',
        ])->where('slug', $slug)->firstOrFail();

        // Related matches same league
        $related = FootballMatch::with(['homeTeam', 'awayTeam', 'league', 'videos'])
            ->where('id', '!=', $match->id)
            ->where('league_id', $match->league_id)
            ->orderBy('match_date', 'desc')
            ->limit(8)
            ->get();

        return Inertia::render('Match', [
            'match'  => $match,
            'related'=> $related,
            'locale' => app()->getLocale(),
        ]);
    }
}
