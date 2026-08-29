<?php
namespace App\Console\Commands;

use App\Models\League;
use App\Models\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ConvertLogosWebpCommand extends Command
{
    protected $signature   = 'logos:convert-webp {--apply : Thực sự convert + update DB, mặc định chỉ dry-run}';
    protected $description = 'Convert logo PNG/JPG self-host (league + team) sang WebP để giảm dung lượng ảnh';

    public function handle(): void
    {
        $apply   = $this->option('apply');
        $manager = new ImageManager(Driver::class);
        $base    = storage_path('app/public');

        $converted = 0;
        $failed    = 0;

        foreach (League::whereNotNull('logo_path')->where('logo_path', 'not like', 'http%')->get() as $league) {
            $relative = str_starts_with($league->logo_path, 'logos/') ? $league->logo_path : 'logos/' . $league->logo_path;
            [$ok, $newRelative] = $this->convertOne($manager, $base, $relative, $apply);
            if ($ok === null) continue; // đã là webp
            if (!$ok) { $failed++; continue; }
            $converted++;
            $newLogoPath = str_starts_with($league->logo_path, 'logos/') ? $newRelative : substr($newRelative, strlen('logos/'));
            $this->line("  [league] {$league->slug}: {$league->logo_path} -> {$newLogoPath}");
            if ($apply) $league->update(['logo_path' => $newLogoPath]);
        }

        foreach (Team::whereNotNull('logo_path')->where('logo_path', 'not like', 'http%')->get() as $team) {
            $relative = 'logos/' . $team->logo_path;
            [$ok, $newRelative] = $this->convertOne($manager, $base, $relative, $apply);
            if ($ok === null) continue;
            if (!$ok) { $failed++; continue; }
            $converted++;
            $newLogoPath = substr($newRelative, strlen('logos/'));
            $this->line("  [team] {$team->slug}: {$team->logo_path} -> {$newLogoPath}");
            if ($apply) $team->update(['logo_path' => $newLogoPath]);
        }

        // ── File tĩnh còn sót lại (vd logo pill hardcode trong AppLayout.vue, không gắn với DB) ──
        foreach (Storage::disk('public')->allFiles('logos') as $relative) {
            [$ok, $newRelative] = $this->convertOne($manager, $base, $relative, $apply);
            if ($ok === null || !$ok) { if ($ok === false) $failed++; continue; }
            $converted++;
            $this->line("  [static] {$relative} -> {$newRelative}");
        }

        $this->newLine();
        $this->info(($apply ? 'Đã convert' : 'Sẽ convert (dry-run, thêm --apply để chạy thật)') . ": {$converted}, lỗi: {$failed}");
    }

    /** @return array{0: bool|null, 1: string} */
    private function convertOne(ImageManager $manager, string $base, string $relative, bool $apply): array
    {
        $ext = strtolower(pathinfo($relative, PATHINFO_EXTENSION));
        $fullPath = "{$base}/{$relative}";

        // DB ghi .webp nhưng file thật lại là .png/.jpg (vd bị gán path thủ
        // công trước khi convert thật sự chạy) — vẫn phải tìm & convert,
        // không được bỏ qua chỉ vì đuôi trong DB đã là webp.
        if ($ext === 'webp') {
            if (file_exists($fullPath)) return [null, $relative];
            foreach (['png', 'jpg', 'jpeg'] as $altExt) {
                $altPath = preg_replace('/\.webp$/i', ".{$altExt}", $fullPath);
                if (file_exists($altPath)) { $fullPath = $altPath; break; }
            }
            if (!file_exists($fullPath)) return [false, $relative];
        } elseif (!in_array($ext, ['png', 'jpg', 'jpeg'])) {
            return [null, $relative];
        } elseif (!file_exists($fullPath)) {
            return [false, $relative];
        }

        $newRelative = preg_replace('/\.(png|jpe?g)$/i', '.webp', $relative);
        $newFullPath = "{$base}/{$newRelative}";

        if ($apply) {
            try {
                $image = $manager->decodePath($fullPath);
                $encoded = $image->encodeUsingFileExtension('webp', quality: 85);
                file_put_contents($newFullPath, (string) $encoded);
                @unlink($fullPath);
            } catch (\Throwable $e) {
                $this->error("  Lỗi convert {$relative}: {$e->getMessage()}");
                return [false, $relative];
            }
        }

        return [true, $newRelative];
    }
}
