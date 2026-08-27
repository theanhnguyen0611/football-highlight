<?php

use App\Jobs\CrawlMatchesJob;
use App\Jobs\DasFootballJob;
use App\Jobs\DownloadVideosJob;
use App\Jobs\MapHoofootVideosJob;
use Illuminate\Support\Facades\Schedule;

// Crawl Highlightly + Hoofoot mỗi 30 phút.
// Job này là thứ duy nhất tiêu quota Highlightly (7500 req/ngày):
// ~24 req cho syncDate 2 ngày + tối đa 30 req cho match details
// → ~2600 req/ngày ở worst case, ~1600 ở steady state.
Schedule::job(new CrawlMatchesJob)->everyThirtyMinutes();

// Bắt video Hoofoot mới sớm cho trận hôm nay + hôm qua — không đụng quota
// Highlightly nên chạy dày hơn CrawlMatchesJob được, rút ngắn độ trễ tối đa
// từ ~30p xuống ~15p trước khi video được đưa vào hàng chờ tải.
Schedule::job(new MapHoofootVideosJob)->everyFifteenMinutes();

// DasFootball mỗi giờ, độc lập với Hoofoot (dùng Playwright, chậm hơn)
Schedule::job(new DasFootballJob)->hourly();

// Download videos mỗi 15 phút
Schedule::job(new DownloadVideosJob)->everyFifteenMinutes();

// Dọn matches cũ > 14 ngày không có video, chạy mỗi đêm lúc 3:00 AM.
// --prune-dasfootball: xoá video DasFootball khi Hoofoot đã có video ready
// cho cùng trận (tránh hiển thị trùng 2 highlight khi Hoofoot đến muộn).
Schedule::command('matches:cleanup --days=14 --prune-dasfootball')->dailyAt('03:00');
