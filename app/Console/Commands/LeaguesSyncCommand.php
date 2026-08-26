<?php

namespace App\Console\Commands;

use App\Models\League;
use App\Services\HighlightlyService;
use Illuminate\Console\Command;

class LeaguesSyncCommand extends Command
{
    protected $signature   = 'leagues:sync {--search= : Debug — tìm tên thật của league theo từ khóa, không insert gì}';
    protected $description = 'Nạp toàn bộ danh sách league từ Highlightly (không cần match) — tránh 404 cho các giải chưa có trận gần đây';

    // Trùng danh sách navLeagues + moreLeagues trong AppLayout.vue — dùng để báo
    // slug nào vẫn còn thiếu sau khi sync (Highlightly không có hoặc tên không khớp).
    private array $menuSlugs = [
        'premier-league', 'uefa-champions-league', 'la-liga', 'bundesliga', 'serie-a',
        'ligue-1', 'uefa-europa-league', 'copa-america', 'euro-championship', 'world-cup',
        'international-friendlies',
        'concacaf-champions-league', 'major-league-soccer', 'super-lig', 'eredivisie',
        'primeira-liga', 'championship', 'league-cup', 'fa-cup', 'dfb-pokal',
        'coupe-de-france', 'copa-del-rey', 'coppa-italia', 'uefa-nations-league',
        'concacaf-gold-cup', 'saudi-pro-league', 'club-friendlies',
    ];

    public function handle(HighlightlyService $highlightly): int
    {
        if ($keyword = $this->option('search')) {
            $results = $highlightly->searchLeagueNames($keyword);
            if (empty($results)) {
                $this->warn("Không tìm thấy league nào chứa \"{$keyword}\".");
                return self::SUCCESS;
            }
            foreach ($results as $r) {
                $this->line("  id={$r['id']}  {$r['name']}");
            }
            return self::SUCCESS;
        }

        $this->info('Syncing leagues from Highlightly...');
        $count = $highlightly->syncAllLeagues();
        $this->info("Done: {$count} league(s) upserted.");

        $missing = collect($this->menuSlugs)
            ->reject(fn ($slug) => League::where('slug', $slug)->exists());

        if ($missing->isEmpty()) {
            $this->info('Tất cả league trong menu đã có trong DB.');
        } else {
            $this->warn('Vẫn thiếu (Highlightly không trả về hoặc tên không khớp slug):');
            $missing->each(fn ($slug) => $this->line("  - {$slug}"));
        }

        return self::SUCCESS;
    }
}
