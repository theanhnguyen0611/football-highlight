<?php

namespace App\Console\Commands;

use App\Models\FootballMatch;
use Illuminate\Console\Command;

class DownloadThumbnailsCommand extends Command
{
    protected $signature   = 'thumbnails:download {--force : Tải lại cả những cái đã có file local}';
    protected $description = 'Tải thumbnail từ Highlightly về web server (không đẩy sang SX65)';

    // Highlightly trả hqdefault (480x360). Thử bản nét hơn trước — YouTube không
    // sinh maxres cho mọi video nên phải hạ dần.
    private const YT_VARIANTS = ['maxresdefault', 'sddefault', 'hqdefault'];

    public function handle(): int
    {
        $dir = storage_path('app/public/thumbnails');
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $query = FootballMatch::whereNotNull('thumbnail_url');
        if (!$this->option('force')) {
            // Chỉ những cái còn là URL http, tức chưa tải về
            $query->where('thumbnail_url', 'like', 'http%');
        }

        $matches = $query->get();
        $this->info("Cần tải: {$matches->count()}");
        if ($matches->isEmpty()) return self::SUCCESS;

        $ok = 0;
        $fail = 0;
        $bar = $this->output->createProgressBar($matches->count());

        foreach ($matches as $match) {
            $remote = $match->getRawOriginal('thumbnail_url');
            if (!$remote || !str_starts_with($remote, 'http')) { $bar->advance(); continue; }

            $file = "{$match->slug}.jpg";
            $path = "{$dir}/{$file}";

            if ($this->fetchBest($remote, $path)) {
                $match->update(['thumbnail_url' => $file]);
                $ok++;
            } else {
                $fail++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Xong: {$ok} tải được, {$fail} lỗi. Serve từ web server qua /storage/thumbnails/");

        return self::SUCCESS;
    }

    // Với ảnh i.ytimg.com thì thử maxres → sd → hq; host khác thì tải thẳng.
    private function fetchBest(string $url, string $path): bool
    {
        if (!str_contains($url, 'i.ytimg.com')) {
            return $this->fetch($url, $path);
        }

        foreach (self::YT_VARIANTS as $variant) {
            $candidate = preg_replace('#/[a-z]+default\.jpg#', "/{$variant}.jpg", $url);
            if ($this->fetch($candidate, $path)) return true;
        }

        return false;
    }

    private function fetch(string $url, string $path): bool
    {
        $tmp = "{$path}.tmp";

        $cmd = sprintf(
            'curl -sfL --max-time 20 -H "User-Agent: Mozilla/5.0" -o %s %s',
            escapeshellarg($tmp),
            escapeshellarg($url)
        );
        exec($cmd, $out, $code);

        // YouTube trả ảnh placeholder xám 120x90 (~1KB) khi variant không tồn tại,
        // và trả HTTP 404 ở phần lớn trường hợp — chặn cả hai.
        if ($code !== 0 || !file_exists($tmp) || filesize($tmp) < 3000) {
            @unlink($tmp);
            return false;
        }

        rename($tmp, $path);
        return true;
    }
}
