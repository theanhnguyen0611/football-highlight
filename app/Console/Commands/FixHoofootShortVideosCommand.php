<?php

namespace App\Console\Commands;

use App\Models\MatchVideo;
use App\Services\CrawlService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class FixHoofootShortVideosCommand extends Command
{
    protected $signature   = 'hoofoot:fix-short-videos {--days=3 : Chỉ quét trận trong N ngày gần đây} {--apply : Thực sự sửa, mặc định chỉ dry-run}';
    protected $description = 'Quét video Hoofoot đã map trước khi có logic chờ EXTENDED (07/29) — nếu giờ đã có bản EXTENDED thì reset để pipeline tự tải lại bản dài';

    public function handle(CrawlService $crawl): void
    {
        $apply = $this->option('apply');
        $days  = (int) $this->option('days');

        $videos = MatchVideo::where('source', 'hoofoot')
            ->whereIn('status', ['pending', 'downloading', 'ready'])
            ->whereNotNull('source_url')
            ->whereHas('match', fn ($q) => $q->where('match_date', '>=', today()->subDays($days)))
            ->with('match.homeTeam', 'match.awayTeam')
            ->get();

        $this->info("Quét {$videos->count()} video Hoofoot trong {$days} ngày gần đây...");

        $fixed = 0;
        foreach ($videos as $video) {
            $result = $crawl->getEmbedUrl($video->source_url);

            if ($result['hasExtended'] && $result['embedUrl'] && $result['embedUrl'] !== $video->embed_url) {
                $matchName = $video->match ? "{$video->match->homeTeam?->name} vs {$video->match->awayTeam?->name}" : "match #{$video->match_id}";
                $this->line("  [fix] {$matchName}: {$video->embed_url} -> {$result['embedUrl']}");

                if ($apply) {
                    if ($video->local_path) {
                        Storage::disk('public')->delete($video->local_path);
                    }
                    $video->update([
                        'embed_url'  => $result['embedUrl'],
                        'local_path' => null,
                        'status'     => 'pending',
                    ]);
                }
                $fixed++;
            }

            usleep(500_000);
        }

        $this->newLine();
        $this->info(($apply ? 'Đã reset' : 'Sẽ reset (dry-run, thêm --apply để chạy thật)') . ": {$fixed} video");
    }
}
