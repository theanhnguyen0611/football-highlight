<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\FootballMatch;
use App\Models\League;
use App\Models\Team;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    private const LOCALES = ['en', 'es', 'pt', 'ar', 'id', 'ja', 'fr', 'de', 'tr', 'hi'];

    private const HTML_LANG = [
        'en' => 'en', 'es' => 'es', 'pt' => 'pt-BR', 'ar' => 'ar', 'id' => 'id',
        'ja' => 'ja', 'fr' => 'fr', 'de' => 'de', 'tr' => 'tr', 'hi' => 'hi',
    ];

    public function index(): Response
    {
        $appUrl = rtrim(config('app.url'), '/');
        $urls   = [];

        $urls[] = ['path' => '/', 'lastmod' => null];
        $urls[] = ['path' => '/matches', 'lastmod' => null];

        League::orderBy('id')->chunk(500, function ($leagues) use (&$urls) {
            foreach ($leagues as $league) {
                $urls[] = ['path' => "/league/{$league->slug}", 'lastmod' => $league->updated_at];
            }
        });

        Team::orderBy('id')->chunk(500, function ($teams) use (&$urls) {
            foreach ($teams as $team) {
                $urls[] = ['path' => "/team/{$team->slug}", 'lastmod' => $team->updated_at];
            }
        });

        FootballMatch::whereHas('videos', fn ($v) => $v->where('status', 'ready'))
            ->orderBy('id')
            ->chunk(500, function ($matches) use (&$urls) {
                foreach ($matches as $match) {
                    $urls[] = ['path' => "/match/{$match->slug}", 'lastmod' => $match->updated_at];
                }
            });

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

        foreach ($urls as $u) {
            $loc = $appUrl . $u['path'];
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . htmlspecialchars($loc, ENT_XML1) . "</loc>\n";
            if ($u['lastmod']) {
                $xml .= '    <lastmod>' . $u['lastmod']->toAtomString() . "</lastmod>\n";
            }
            foreach (self::LOCALES as $locale) {
                $href = $locale === 'en' ? $appUrl . $u['path'] : $appUrl . '/' . $locale . $u['path'];
                $xml .= '    <xhtml:link rel="alternate" hreflang="' . self::HTML_LANG[$locale] . '" href="' . htmlspecialchars($href, ENT_XML1) . '" />' . "\n";
            }
            $xml .= '    <xhtml:link rel="alternate" hreflang="x-default" href="' . htmlspecialchars($loc, ENT_XML1) . '" />' . "\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
