<?php

namespace App\Jobs;

use App\Models\FootballMatch;
use App\Services\CrawlService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CrawlMatchesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries   = 3;

    public function handle(CrawlService $crawl): void
    {
        Log::info('CrawlMatchesJob: start');

        $slugs = $crawl->fetchSitemapSlugs();
        $new   = 0;

        foreach ($slugs as $item) {
            if (FootballMatch::where('slug', $item['slug'])->exists()) {
                continue;
            }

            sleep(1);
            $data = $crawl->crawlMatch($item['url'], $item['slug']);
            if (!$data) continue;

            $crawl->saveMatch($data);
            $new++;

            Log::info('CrawlMatchesJob: new match', ['slug' => $item['slug']]);
        }

        Log::info('CrawlMatchesJob: done', ['new' => $new]);
    }
}
