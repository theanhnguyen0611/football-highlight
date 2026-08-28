<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\FootballMatch;
use App\Models\League;
use App\Models\Team;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        // Lấy Featured trước để loại các match này khỏi Latest -> không hiện trùng 2 nơi
        $featured = $this->getFeaturedHighlights();
        $featuredIds = array_column($featured, 'id');

        $matches = $this->latestHighlightsQuery($featuredIds)->paginate(20)->withQueryString();

        view()->share('seo', app(SeoService::class)->home());
        // Preload ảnh nền 2 card đầu (grid 2 cột trên mobile, cả 2 đều nằm trong viewport
        // nên đều có thể là LCP element) để browser tải song song với lúc tải JS bundle,
        // thay vì phải đợi Vue render xong mới bắt đầu tải ảnh.
        view()->share('preloadImages', array_filter(array_map(
            fn ($m) => $m['league']['background_url_full'] ?? null,
            array_slice($featured, 0, 2)
        )));

        return Inertia::render('Home', [
            'matches'             => $matches,
            'leagues'             => $this->getLeagues(),
            'popular_teams'       => $this->getPopularTeams(),
            'featured_highlights' => $featured,
            'locale'              => app()->getLocale(),
        ]);
    }

    public function matches(Request $request): Response
    {
        // Cùng query với Latest Highlights ở trang chủ để số trang khớp nhau xuyên suốt
        $featuredIds = array_column($this->getFeaturedHighlights(), 'id');
        $matches = $this->latestHighlightsQuery($featuredIds)->paginate(20)->withQueryString();

        view()->share('seo', app(SeoService::class)->matches($matches->currentPage()));

        return Inertia::render('Matches', [
            'matches'       => $matches,
            'leagues'       => $this->getLeagues(),
            'popular_teams' => $this->getPopularTeams(),
            'locale'        => app()->getLocale(),
        ]);
    }

    public function league(Request $request): Response
    {
        $league_slug = $request->route('league_slug');
        $league = League::where('slug', $league_slug)->firstOrFail();

        $matches = FootballMatch::with(['homeTeam.translations', 'awayTeam.translations', 'league', 'videos'])
            ->where('league_id', $league->id)
            ->whereHas('videos', fn($v) => $v->where('status', 'ready'))
            ->orderBy('match_date', 'desc')
            ->paginate(20)
            ->withQueryString();

        view()->share('seo', app(SeoService::class)->league($league));

        return Inertia::render('League', [
            'league'        => [
                'id'                 => $league->id,
                'name'               => $league->name,
                'slug'               => $league->slug,
                'logo_url'           => $league->logo_url,
                'primary_color'      => $league->primary_color,
                'country'            => $league->country,
                'background_url_full'=> $league->background_url_full,
            ],
            'matches'       => $matches,
            'leagues'       => $this->getLeagues(),
            'popular_teams' => $this->getPopularTeams(),
            'locale'        => app()->getLocale(),
        ]);
    }

    public function search(Request $request): Response
    {
        $q = trim($request->get('q', ''));

        $matches = collect();
        $teams   = collect();
        $leagues = collect();

        if ($q !== '') {
            $matches = FootballMatch::with(['homeTeam.translations', 'awayTeam.translations', 'league', 'videos'])
                ->whereHas('videos', fn($v) => $v->where('status', 'ready'))
                ->where(function ($sq) use ($q) {
                    $sq->whereHas('homeTeam', fn($t) => $t->where('name', 'like', "%{$q}%"))
                       ->orWhereHas('awayTeam', fn($t) => $t->where('name', 'like', "%{$q}%"));
                })
                ->orderBy('match_date', 'desc')
                ->paginate(16)
                ->withQueryString();

            $teams = Team::where('name', 'like', "%{$q}%")
                ->withCount(['homeMatches as home_count', 'awayMatches as away_count'])
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
                ->take(8)
                ->values();

            $leagues = League::where('name', 'like', "%{$q}%")
                ->withCount('matches')
                ->get()
                ->map(fn($l) => [
                    'id'          => $l->id,
                    'name'        => $l->name,
                    'slug'        => $l->slug,
                    'logo_url'    => $l->logo_url,
                    'match_count' => $l->matches_count,
                ])
                ->take(6)
                ->values();
        }

        view()->share('seo', app(SeoService::class)->search($q));

        return Inertia::render('Search', [
            'q'             => $q,
            'matches'       => $matches,
            'teams'         => $teams,
            'leagues'       => $leagues,
            'allLeagues'    => $this->getLeagues(),
            'popular_teams' => $this->getPopularTeams(),
            'locale'        => app()->getLocale(),
        ]);
    }

    public function team(Request $request): Response
    {
        $slug = $request->route('slug');
        $team = Team::where('slug', $slug)->with('translations')->firstOrFail();

        $matches = FootballMatch::with(['homeTeam.translations', 'awayTeam.translations', 'league', 'videos'])
            ->where(function ($q) use ($team) {
                $q->where('home_team_id', $team->id)
                  ->orWhere('away_team_id', $team->id);
            })
            ->whereHas('videos', fn($v) => $v->where('status', 'ready'))
            ->orderBy('match_date', 'desc')
            ->paginate(20)
            ->withQueryString();

        view()->share('seo', app(SeoService::class)->team($team));

        return Inertia::render('Team', [
            'team' => [
                'id'           => $team->id,
                'name'         => $team->name,
                'slug'         => $team->slug,
                'type'         => $team->type,
                'country'      => $team->country,
                'logo_url'     => $team->logo_url,
                'initials'     => $team->initials,
                'translations' => $team->translations->map(fn($tr) => [
                    'locale' => $tr->locale,
                    'name'   => $tr->name,
                ])->toArray(),
            ],
            'matches'       => $matches,
            'leagues'       => $this->getLeagues(),
            'popular_teams' => $this->getPopularTeams(),
            'locale'        => app()->getLocale(),
        ]);
    }

    public function show(Request $request): Response
    {
        $slug = $request->route('slug');
        $match = FootballMatch::with([
            'homeTeam.translations',
            'awayTeam.translations',
            'league',
            'videos',
            'events' => fn($q) => $q->orderBy('minute')->orderBy('extra_minute'),
        ])->where('slug', $slug)->firstOrFail();

        $related = FootballMatch::with(['homeTeam.translations', 'awayTeam.translations', 'league', 'videos'])
            ->where('id', '!=', $match->id)
            ->where('league_id', $match->league_id)
            ->whereHas('videos', fn($v) => $v->where('status', 'ready'))
            ->orderBy('match_date', 'desc')
            ->limit(8)
            ->get();

        view()->share('seo', app(SeoService::class)->match($match));

        return Inertia::render('Match', [
            'match'         => $this->serializeMatch($match),
            'related'       => $related->map(fn($m) => $this->serializeRelated($m)),
            'leagues'       => $this->getLeagues(),
            'popular_teams' => $this->getPopularTeams(),
            'locale'        => app()->getLocale(),
        ]);
    }

    public function embed(Request $request): \Illuminate\Http\Response
    {
        $slug  = $request->route('slug');
        $match = FootballMatch::with(['homeTeam', 'awayTeam', 'league', 'videos'])
            ->where('slug', $slug)
            ->firstOrFail();

        view()->share('seo', array_merge(
            app(SeoService::class)->match($match),
            ['noindex' => true]
        ));

        return Inertia::render('Embed', [
            'match' => [
                'slug'       => $match->slug,
                'home_score' => $match->home_score,
                'away_score' => $match->away_score,
                'match_date' => $match->match_date,
                'home_team'  => $match->homeTeam ? [
                    'name'     => $match->homeTeam->name,
                    'logo_url' => $match->homeTeam->logo_url,
                    'initials' => $match->homeTeam->initials,
                ] : null,
                'away_team'  => $match->awayTeam ? [
                    'name'     => $match->awayTeam->name,
                    'logo_url' => $match->awayTeam->logo_url,
                    'initials' => $match->awayTeam->initials,
                ] : null,
                'league'     => $match->league ? [
                    'name'     => $match->league->name,
                    'logo_url' => $match->league->logo_url,
                ] : null,
                'videos'     => $match->videos->map(fn($v) => [
                    'id'               => $v->id,
                    'status'           => $v->status,
                    'video_type'       => $v->video_type,
                    // Không dùng $v->stream_url trực tiếp — accessor đó
                    // fallback về embed_url/source_url (link Hoofoot/
                    // DasFootball) khi chưa tự host xong. Trang embed này
                    // được nhúng công khai, JSON props nằm ngay trong
                    // HTML source, không được lộ link nguồn.
                    'stream_url'       => $v->status === 'ready'
                        ? route('api.videos.stream', $v->id)
                        : null,
                    'quality'          => $v->quality,
                    'language'         => $v->language,
                    'duration_seconds' => $v->duration_seconds,
                ])->values(),
            ],
        ])
        ->toResponse($request)
        ->withHeaders([
            'X-Frame-Options'         => 'ALLOWALL',
            'Content-Security-Policy' => "frame-ancestors *",
        ]);
    }

    // ── Private helpers ────────────────────────────────────────

    private function latestHighlightsQuery(array $excludeIds = [])
    {
        return FootballMatch::with([
            'homeTeam.translations',
            'awayTeam.translations',
            'league',
            'videos',
        ])
        ->whereHas('videos', fn($v) => $v->where('status', 'ready'))
        ->when(!empty($excludeIds), fn($q) => $q->whereNotIn('id', $excludeIds))
        ->orderBy('match_date', 'desc');
    }

    private function getFeaturedHighlights(): array
    {
        $teamSlugs = [
            // Clubs
            'real-madrid', 'barcelona', 'manchester-city', 'liverpool', 'manchester-united',
            'arsenal', 'chelsea', 'bayern-munich', 'paris-saint-germain',
            'juventus', 'ac-milan', 'inter-milan', 'napoli', 'borussia-dortmund',
            'atletico-madrid', 'tottenham',
            // National teams
            'argentina', 'france', 'spain', 'germany', 'england', 'brazil', 'portugal',
        ];

        return FootballMatch::with(['homeTeam.translations', 'awayTeam.translations', 'league', 'videos'])
            ->where('match_date', '>=', now()->subDays(7))
            ->whereHas('videos', fn($v) => $v->where('status', 'ready'))
            ->where(function ($q) use ($teamSlugs) {
                $q->whereHas('homeTeam', fn($t) => $t->whereIn('slug', $teamSlugs))
                  ->orWhereHas('awayTeam', fn($t) => $t->whereIn('slug', $teamSlugs));
            })
            ->orderBy('match_date', 'desc')
            ->get()
            ->map(fn($m) => $this->serializeRelated($m))
            ->toArray();
    }

    private function getLeagues(): \Illuminate\Support\Collection
    {
        // Cache dạng array (không phải Collection) vì Laravel chặn unserialize object
        // trong cache (config('cache.serializable_classes') === false) để chống object injection.
        return collect(Cache::remember('home.leagues', 900, function () {
            return League::withCount('matches')
                ->orderByDesc('matches_count')
                ->get()
                ->map(fn($l) => [
                    'id'          => $l->id,
                    'name'        => $l->name,
                    'slug'        => $l->slug,
                    'logo_url'    => $l->logo_url,
                    'match_count' => $l->matches_count,
                ])
                ->toArray();
        }));
    }

    private function getPopularTeams(): \Illuminate\Support\Collection
    {
        return collect(Cache::remember('home.popular_teams', 900, function () {
            return Team::withCount([
                    'homeMatches as home_count',
                    'awayMatches as away_count',
                ])
                ->with('translations')
                ->orderByRaw('(home_count + away_count) DESC')
                ->limit(12)
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
                ->toArray();
        }));
    }

    private function serializeRelated(FootballMatch $match): array
    {
        return [
            'id'            => $match->id,
            'slug'          => $match->slug,
            'match_date'    => $match->match_date,
            'kick_off_time' => $match->kick_off_time,
            'thumbnail_url' => $match->thumbnail_url,
            'home_score'    => $match->home_score,
            'away_score'    => $match->away_score,
            'venue'         => $match->venue,
            'round'         => $match->round,
            'home_team'     => $match->homeTeam ? [
                'id'           => $match->homeTeam->id,
                'name'         => $match->homeTeam->name,
                'slug'         => $match->homeTeam->slug,
                'logo_url'     => $match->homeTeam->logo_url,
                'initials'     => $match->homeTeam->initials,
                'translations' => $match->homeTeam->translations->map(fn($tr) => [
                    'locale' => $tr->locale,
                    'name'   => $tr->name,
                ])->toArray(),
            ] : null,
            'away_team'     => $match->awayTeam ? [
                'id'           => $match->awayTeam->id,
                'name'         => $match->awayTeam->name,
                'slug'         => $match->awayTeam->slug,
                'logo_url'     => $match->awayTeam->logo_url,
                'initials'     => $match->awayTeam->initials,
                'translations' => $match->awayTeam->translations->map(fn($tr) => [
                    'locale' => $tr->locale,
                    'name'   => $tr->name,
                ])->toArray(),
            ] : null,
            'league'        => $match->league ? [
                'id'                 => $match->league->id,
                'name'               => $match->league->name,
                'slug'               => $match->league->slug,
                'logo_url'           => $match->league->logo_url,
                'primary_color'      => $match->league->primary_color,
                'background_url_full'=> $match->league->background_url_full,
            ] : null,
        ];
    }

    private function serializeMatch(FootballMatch $match): array
    {
        return [
            'id'               => $match->id,
            'slug'             => $match->slug,
            'match_date'       => $match->match_date,
            'kick_off_time'    => $match->kick_off_time,
            'home_score'       => $match->home_score,
            'away_score'       => $match->away_score,
            'score_penalties'  => $match->score_penalties,
            'round'            => $match->round,
            'venue'            => $match->venue,
            'referee'          => $match->referee,
            'statistics'       => $match->statistics,
            'thumbnail_url'    => $match->thumbnail_url,
            'home_team'        => $match->homeTeam ? [
                'id'             => $match->homeTeam->id,
                'name'           => $match->homeTeam->name,
                'slug'           => $match->homeTeam->slug,
                'logo_url'       => $match->homeTeam->logo_url,
                'initials'       => $match->homeTeam->initials,
                'highlightly_id' => $match->homeTeam->highlightly_id,
                'translations'   => $match->homeTeam->translations->map(fn($tr) => [
                    'locale' => $tr->locale,
                    'name'   => $tr->name,
                ])->toArray(),
            ] : null,
            'away_team'        => $match->awayTeam ? [
                'id'             => $match->awayTeam->id,
                'name'           => $match->awayTeam->name,
                'slug'           => $match->awayTeam->slug,
                'logo_url'       => $match->awayTeam->logo_url,
                'initials'       => $match->awayTeam->initials,
                'highlightly_id' => $match->awayTeam->highlightly_id,
                'translations'   => $match->awayTeam->translations->map(fn($tr) => [
                    'locale' => $tr->locale,
                    'name'   => $tr->name,
                ])->toArray(),
            ] : null,
            'league'           => $match->league ? [
                'id'            => $match->league->id,
                'name'          => $match->league->name,
                'slug'          => $match->league->slug,
                'logo_url'      => $match->league->logo_url,
                'primary_color' => $match->league->primary_color,
            ] : null,
            'videos'           => ($match->videos ?? collect())
                ->sortBy(fn($v) => match($v->source) {
                    'hoofoot'     => 0,
                    'dasfootball' => 1,
                    default       => 2,
                })
                ->map(fn($v) => [
                'id'               => $v->id,
                'source'           => $v->source,
                'video_type'       => $v->video_type,
                'status'           => $v->status,
                'quality'          => $v->quality,
                'language'         => $v->language,
                'duration_seconds' => $v->duration_seconds,
                'file_size_mb'     => $v->file_size_mb,
            ])->values()->toArray(),
            'events'           => ($match->events ?? collect())->map(fn($e) => [
                'id'           => $e->id,
                'team_id'      => $e->team_id,
                'minute'       => $e->minute,
                'extra_minute' => $e->extra_minute,
                'type'         => $e->type,
                'player_name'  => $e->player_name,
                'assist_name'  => $e->assist_name,
            ])->values()->toArray(),
        ];
    }
}
