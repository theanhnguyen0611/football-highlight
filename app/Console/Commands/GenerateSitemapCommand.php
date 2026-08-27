<?php

namespace App\Console\Commands;

use App\Models\FootballMatch;
use App\Models\League;
use App\Models\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

// Sinh sitemap TĨNH ra public/ (không query DB mỗi request nữa như
// SitemapController cũ). public/sitemap.xml là index, trỏ tới các sitemap
// con theo type — chuẩn <sitemapindex> của Google, dễ theo dõi league/team/
// match riêng biệt trong Search Console.
class GenerateSitemapCommand extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Sinh sitemap tĩnh (index + con: static/leagues/teams/matches) ra public/';

    private const LOCALES = ['en', 'es', 'pt', 'ar', 'id', 'ja', 'fr', 'de', 'tr', 'hi'];

    private const HTML_LANG = [
        'en' => 'en', 'es' => 'es', 'pt' => 'pt-BR', 'ar' => 'ar', 'id' => 'id',
        'ja' => 'ja', 'fr' => 'fr', 'de' => 'de', 'tr' => 'tr', 'hi' => 'hi',
    ];

    public function handle(): int
    {
        $appUrl = rtrim(config('app.url'), '/');

        $static = [
            ['path' => '/', 'lastmod' => now()],
            ['path' => '/matches', 'lastmod' => now()],
        ];
        $this->writeUrlset('sitemap-static.xml', $static, $appUrl);

        // Chỉ đưa vào sitemap league/team có ít nhất 1 trận đã có video ready —
        // trang không có trận nào (do lọc status=ready ở HomeController::league()/
        // team()) sẽ hiện rỗng, đưa vào sitemap chỉ tổ phí crawl budget + thin content.
        $leagues = [];
        League::whereHas('matches.videos', fn ($v) => $v->where('status', 'ready'))
            ->orderBy('id')
            ->chunk(500, function ($rows) use (&$leagues) {
                foreach ($rows as $l) {
                    $leagues[] = ['path' => "/league/{$l->slug}", 'lastmod' => $l->updated_at];
                }
            });
        $this->writeUrlset('sitemap-leagues.xml', $leagues, $appUrl);

        $teams = [];
        Team::where(function ($q) {
                $q->whereHas('homeMatches.videos', fn ($v) => $v->where('status', 'ready'))
                  ->orWhereHas('awayMatches.videos', fn ($v) => $v->where('status', 'ready'));
            })
            ->orderBy('id')
            ->chunk(500, function ($rows) use (&$teams) {
                foreach ($rows as $t) {
                    $teams[] = ['path' => "/team/{$t->slug}", 'lastmod' => $t->updated_at];
                }
            });
        $this->writeUrlset('sitemap-teams.xml', $teams, $appUrl);

        $matches = [];
        FootballMatch::whereHas('videos', fn ($v) => $v->where('status', 'ready'))
            ->orderBy('id')
            ->chunk(500, function ($rows) use (&$matches) {
                foreach ($rows as $m) {
                    $matches[] = ['path' => "/match/{$m->slug}", 'lastmod' => $m->updated_at];
                }
            });
        $this->writeUrlset('sitemap-matches.xml', $matches, $appUrl);

        $this->writeIndex($appUrl, ['sitemap-static.xml', 'sitemap-leagues.xml', 'sitemap-teams.xml', 'sitemap-matches.xml']);

        $this->info(sprintf(
            'Sitemap: %d static, %d league, %d team, %d match',
            count($static), count($leagues), count($teams), count($matches)
        ));

        return self::SUCCESS;
    }

    private function writeUrlset(string $filename, array $urls, string $appUrl): void
    {
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

        File::put(public_path($filename), $xml);
    }

    private function writeIndex(string $appUrl, array $files): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($files as $file) {
            $xml .= "  <sitemap>\n";
            $xml .= '    <loc>' . $appUrl . '/' . $file . "</loc>\n";
            $xml .= '    <lastmod>' . now()->toAtomString() . "</lastmod>\n";
            $xml .= "  </sitemap>\n";
        }

        $xml .= '</sitemapindex>';

        File::put(public_path('sitemap.xml'), $xml);
    }
}
