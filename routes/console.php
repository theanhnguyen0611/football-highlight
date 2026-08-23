<?php

use App\Jobs\CrawlMatchesJob;
use App\Jobs\DownloadVideosJob;
use Illuminate\Support\Facades\Schedule;

// Crawl sitemap mỗi 15 phút
Schedule::job(new CrawlMatchesJob)->everyFifteenMinutes();

// Download videos mỗi 5 phút
Schedule::job(new DownloadVideosJob)->everyFifteenMinutes();

// Dọn matches cũ > 14 ngày không có video, chạy mỗi đêm lúc 3:00 AM
Schedule::command('matches:cleanup --days=14')->dailyAt('03:00');
