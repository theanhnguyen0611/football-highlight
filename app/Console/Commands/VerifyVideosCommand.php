<?php

namespace App\Console\Commands;

use App\Models\MatchVideo;
use App\Services\DownloadService;
use Illuminate\Console\Command;

class VerifyVideosCommand extends Command
{
    protected $signature = 'videos:verify
                            {--fix : Đặt lại video hỏng về pending để tải lại}
                            {--prune : Xoá thư mục trên SX65 không còn video nào trong DB trỏ tới}';

    protected $description = 'Đối chiếu video status=ready trong DB với file thật trên SX65';

    public function __construct(private DownloadService $downloader)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $videos = MatchVideo::where('status', 'ready')
            ->whereNotNull('local_path')
            ->with('match')
            ->get();

        $this->info("Video ready trong DB: {$videos->count()}");
        if ($videos->isEmpty()) return self::SUCCESS;

        // Một lần ssh cho tất cả — mỗi video một lần ssh thì hàng trăm video sẽ rất chậm
        $states = $this->downloader->checkOnStorage($videos->pluck('local_path')->all());

        if (empty($states)) {
            $this->error('Không kiểm tra được SX65 (ssh lỗi hoặc CDN chưa cấu hình).');
            return self::FAILURE;
        }

        $broken = [];
        foreach ($videos as $v) {
            $state = $states[$v->local_path] ?? 'missing';
            if ($state === 'ok') continue;

            $broken[] = $v;
            $this->warn(sprintf(
                '  [%s] #%d %s — %s',
                str_pad($state, 7),
                $v->id,
                $v->match?->slug ?? '(không có match)',
                $v->local_path
            ));
        }

        $this->newLine();
        $this->info(sprintf('OK: %d | Hỏng: %d', $videos->count() - count($broken), count($broken)));

        if ($broken && $this->option('fix')) {
            foreach ($broken as $v) {
                // Về pending + xoá local_path để downloadAllPending() nhặt lại
                $v->update(['status' => 'pending', 'local_path' => null, 'file_size_mb' => null, 'duration_seconds' => null]);
            }
            $this->info('Đã đặt lại ' . count($broken) . ' video về pending — lượt download tới sẽ tải lại.');
        } elseif ($broken) {
            $this->comment('Chạy lại với --fix để đặt chúng về pending.');
        }

        if ($this->option('prune')) {
            $this->prune($videos);
        }

        return self::SUCCESS;
    }

    // Thư mục trên SX65 mà không video nào trong DB trỏ tới → rác từ lần tải hỏng
    // hoặc từ match đã bị xoá.
    private function prune($videos): void
    {
        $this->newLine();
        $this->info('Quét thư mục thừa trên SX65...');

        $dirs = $this->downloader->listStorageDirs();
        if (empty($dirs)) {
            $this->comment('  Không liệt kê được thư mục nào.');
            return;
        }

        // Giữ cả thư mục rendition (highlights/{slug}/{subdir}) lẫn thư mục cha
        $known = [];
        foreach (MatchVideo::whereNotNull('local_path')->pluck('local_path') as $p) {
            $renditionDir = dirname($p);
            $known[$renditionDir]       = true;
            $known[dirname($renditionDir)] = true;
        }

        $orphans = array_values(array_filter($dirs, fn($d) => !isset($known[trim($d, '/')])));

        if (empty($orphans)) {
            $this->info('  Không có thư mục thừa.');
            return;
        }

        $this->warn('  Thư mục không có row DB nào trỏ tới: ' . count($orphans));
        foreach ($orphans as $d) $this->line("    {$d}");

        if (!$this->confirm('Xoá những thư mục này trên SX65?', false)) {
            $this->comment('  Bỏ qua.');
            return;
        }

        $deleted = 0;
        foreach ($orphans as $d) {
            if ($this->downloader->deleteFromStorage($d)) $deleted++;
        }
        $this->info("  Đã xoá {$deleted} thư mục.");
    }
}
