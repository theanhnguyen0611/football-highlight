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

    // 16:9, đủ nét cho card trong lưới kể cả màn retina, mà vẫn nhẹ.
    private const OUT_W       = 640;
    private const OUT_H       = 360;
    private const WEBP_QUALITY = 80;

    public function handle(): int
    {
        if (!function_exists('imagewebp')) {
            $this->error('PHP GD chưa có WebP. Cài php-gd (Ubuntu: apt install php8.4-gd) rồi chạy lại.');
            return self::FAILURE;
        }

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

        $ok = $fail = 0;
        $bytes = 0;
        $bar = $this->output->createProgressBar($matches->count());

        foreach ($matches as $match) {
            $remote = $match->getRawOriginal('thumbnail_url');
            if (!$remote || !str_starts_with($remote, 'http')) { $bar->advance(); continue; }

            $file = "{$match->slug}.webp";
            $path = "{$dir}/{$file}";

            if ($this->fetchBest($remote, $path)) {
                $match->update(['thumbnail_url' => $file]);
                $bytes += filesize($path);
                // Bản .jpg từ phiên bản trước của lệnh này
                @unlink("{$dir}/{$match->slug}.jpg");
                $ok++;
            } else {
                $fail++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info(sprintf(
            'Xong: %d tải được, %d lỗi. Trung bình %s KB/ảnh, serve qua /storage/thumbnails/',
            $ok,
            $fail,
            $ok ? number_format($bytes / $ok / 1024, 1) : '0'
        ));

        return self::SUCCESS;
    }

    // Với ảnh i.ytimg.com thì thử maxres → sd → hq; host khác thì tải thẳng.
    private function fetchBest(string $url, string $path): bool
    {
        if (!str_contains($url, 'i.ytimg.com')) {
            return $this->fetchAndConvert($url, $path);
        }

        foreach (self::YT_VARIANTS as $variant) {
            $candidate = preg_replace('#/[a-z]+default\.jpg#', "/{$variant}.jpg", $url);
            if ($this->fetchAndConvert($candidate, $path)) return true;
        }

        return false;
    }

    private function fetchAndConvert(string $url, string $path): bool
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

        $done = $this->toWebp($tmp, $path);
        @unlink($tmp);

        return $done;
    }

    // Crop giữa về 16:9 rồi scale xuống 640x360, xuất WebP.
    // Bản sddefault/hqdefault của YouTube là 4:3 có viền đen trên dưới — crop
    // theo công thức dưới cắt đúng phần viền đó, nên mọi variant đều ra 16:9 thật.
    private function toWebp(string $srcPath, string $outPath): bool
    {
        $data = @file_get_contents($srcPath);
        if (!$data) return false;

        $src = @imagecreatefromstring($data);
        if (!$src) return false;

        $w = imagesx($src);
        $h = imagesy($src);

        $cropW = (int) min($w, $h * 16 / 9);
        $cropH = (int) min($h, $w * 9 / 16);
        $cropX = (int) (($w - $cropW) / 2);
        $cropY = (int) (($h - $cropH) / 2);

        $dst = imagecreatetruecolor(self::OUT_W, self::OUT_H);
        imagecopyresampled($dst, $src, 0, 0, $cropX, $cropY, self::OUT_W, self::OUT_H, $cropW, $cropH);

        $done = imagewebp($dst, $outPath, self::WEBP_QUALITY);

        imagedestroy($src);
        imagedestroy($dst);

        return $done && file_exists($outPath) && filesize($outPath) > 0;
    }
}
