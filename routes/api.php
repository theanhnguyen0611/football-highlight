<?php

use App\Http\Controllers\Api\MatchController;
use App\Http\Controllers\Api\LeagueController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\VideoController;
use App\Http\Controllers\Api\Admin\StatsController;
use App\Http\Controllers\Api\Admin\CrawlController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/matches', [MatchController::class, 'index']);
Route::get('/matches/{slug}', [MatchController::class, 'show']);
Route::get('/leagues', [LeagueController::class, 'index']);
Route::get('/teams', [TeamController::class, 'index']);
Route::get('/teams/{team}/matches', [TeamController::class, 'matches']);
Route::get('/videos/{id}/stream', [VideoController::class, 'stream'])->name('api.videos.stream');

// HLS serve với signed URL
Route::get('/hls/{path}', [VideoController::class, 'serve'])
    ->where('path', '.*')
    ->name('hls.serve');

// Admin routes (protected bằng API key)
Route::prefix('admin')->middleware('auth.admin')->group(function () {
    Route::get('/stats', [StatsController::class, 'index']);
    Route::post('/crawl', [CrawlController::class, 'crawl']);
    Route::post('/download/{matchId}', [CrawlController::class, 'download']);
    Route::post('/retry-errors', [CrawlController::class, 'retryErrors']);
});
