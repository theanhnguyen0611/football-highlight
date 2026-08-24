<?php

namespace App\Console\Commands;

use App\Models\FootballMatch;
use App\Services\CrawlService;
use Illuminate\Console\Command;

class ScanHoofootMismatchesCommand extends Command
{
    protected $signature   = 'hoofoot:scan-mismatches {--days=3}';
    protected $description = 'Tìm DB matches không map được với Hoofoot slug → bổ sung HOOFOOT_ALIASES';

    public function handle(CrawlService $crawl): void
    {
        $days     = (int) $this->option('days');
        $listings = $crawl->crawlHoofootListings();

        $this->info('Hoofoot slugs found: ' . count($listings));

        // Group slugs by date
        $slugsByDate = [];
        foreach ($listings as $slug => $_) {
            $parts   = explode('_', $slug);
            $dateStr = implode('-', array_slice($parts, -3));
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
                $slugsByDate[$dateStr][] = $slug;
            }
        }

        $cutoff  = now()->subDays($days)->format('Y-m-d');
        $matches = FootballMatch::with(['homeTeam', 'awayTeam'])
            ->where('match_status', 'finished')
            ->whereIn(\DB::raw('DATE(match_date)'), array_keys($slugsByDate))
            ->where('match_date', '>=', $cutoff)
            ->whereDoesntHave('videos', fn($q) => $q->whereIn('status', ['pending', 'ready']))
            ->get();

        $this->info("DB matches without video (last {$days} days): " . $matches->count());
        $this->newLine();

        $mismatches = [];
        foreach ($matches as $match) {
            $dateStr  = $match->match_date->format('Y-m-d');
            $slugs    = $slugsByDate[$dateStr] ?? [];
            $homeName = strtolower(str_replace([' ', '.', '-'], '_', $match->homeTeam->name));
            $awayName = strtolower(str_replace([' ', '.', '-'], '_', $match->awayTeam->name));

            // Tìm slug match được
            $matched = null;
            foreach ($slugs as $slug) {
                $lower = strtolower($slug);
                if (str_contains($lower, $homeName) || str_contains($lower, $awayName)) {
                    $matched = $slug;
                    break;
                }
            }

            if (!$matched) {
                // Tìm slug gần nhất cho ngày đó
                $candidates = array_filter($slugs, fn($s) => true); // all slugs that day
                $mismatches[] = [
                    'match'      => "{$match->homeTeam->name} vs {$match->awayTeam->name} ({$dateStr})",
                    'home_key'   => $homeName,
                    'away_key'   => $awayName,
                    'candidates' => array_slice($candidates, 0, 10),
                ];
            }
        }

        if (empty($mismatches)) {
            $this->info('Không có mismatch nào!');
            return;
        }

        $this->warn(count($mismatches) . ' matches không map được:');
        $this->newLine();

        foreach ($mismatches as $m) {
            $this->line("<fg=yellow>{$m['match']}</>");
            $this->line("  DB keys: <fg=cyan>{$m['home_key']}</> | <fg=cyan>{$m['away_key']}</>");
            if ($m['candidates']) {
                $this->line('  Hoofoot slugs ngày đó:');
                foreach ($m['candidates'] as $slug) {
                    $this->line("    - {$slug}");
                }
            } else {
                $this->line('  (Hoofoot không có slug nào cho ngày này)');
            }
            $this->newLine();
        }

        $this->info('→ Với các case trên, thêm alias vào HOOFOOT_ALIASES trong CrawlService.php');
    }
}
