<?php

namespace App\Console\Commands;

use App\Models\MatchVideo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetDataCommand extends Command
{
    protected $signature   = 'reset:data {--force : Skip confirmation}';
    protected $description = 'Xoá toàn bộ matches + videos + storage SX65, rồi crawl lại';

    public function handle(): void
    {
        if (!$this->option('force') && !$this->confirm('Xoá toàn bộ matches, videos, events và storage SX65?')) {
            $this->info('Cancelled.');
            return;
        }

        // 1. Lấy danh sách local_path trước khi xoá
        $localPaths = MatchVideo::whereNotNull('local_path')->pluck('local_path');

        // 2. Xoá DB (đúng thứ tự FK)
        $this->info('Clearing database...');
        DB::table('match_events')->delete();
        DB::table('match_videos')->delete();
        DB::table('matches')->delete();
        $this->line('  DB cleared.');

        // 3. Xoá storage SX65
        $ssh  = config('services.cdn.sx65_ssh');
        $base = config('services.cdn.sx65_path');

        if ($ssh && $base) {
            $this->info('Clearing SX65 storage...');
            $dirs = ['highlights', 'full-matches'];
            foreach ($dirs as $dir) {
                $cmd = sprintf('ssh %s "rm -rf %s/%s/*"', escapeshellarg($ssh), $base, $dir);
                shell_exec($cmd);
                $this->line("  Cleared {$base}/{$dir}/");
            }
        } else {
            $this->warn('SX65 not configured, skipping storage cleanup.');
        }

        // 4. Crawl lại
        $this->info('Re-crawling...');
        $this->call('crawl:matches', ['--days' => 7]);

        $this->info('Reset complete!');
    }
}
