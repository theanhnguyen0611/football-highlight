<?php

use App\Jobs\CrawlMatchesJob;
use App\Jobs\DasFootballJob;
use App\Jobs\DownloadVideosJob;
use Illuminate\Support\Facades\Schedule;

// Crawl Highlightly + Hoofoot mỗi 15 phút
Schedule::job(new CrawlMatchesJob)->everyFifteenMinutes();

// DasFootball fallback mỗi giờ (dùng Playwright, chậm hơn)
Schedule::job(new DasFootballJob)->hourly();

// Download videos mỗi 15 phút
Schedule::job(new DownloadVideosJob)->everyFifteenMinutes();

// Dọn matches cũ > 14 ngày không có video, chạy mỗi đêm lúc 3:00 AM
Schedule::command('matches:cleanup --days=14')->dailyAt('03:00');
