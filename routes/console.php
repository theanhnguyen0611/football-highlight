<?php

use App\Jobs\CrawlMatchesJob;
use App\Jobs\DownloadVideosJob;
use Illuminate\Support\Facades\Schedule;

// Crawl sitemap mỗi 15 phút
Schedule::job(new CrawlMatchesJob)->everyFifteenMinutes();

// Download videos mỗi 5 phút
Schedule::job(new DownloadVideosJob)->everyFiveMinutes();
