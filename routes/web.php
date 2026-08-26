<?php

use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\AdminController;
use App\Http\Controllers\Web\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/admin', [AdminController::class, 'index'])->name('admin');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// Default EN
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/matches', [HomeController::class, 'matches'])->name('matches');
Route::get('/embed/match/{slug}', [HomeController::class, 'embed'])->name('match.embed');
Route::get('/match/{slug}', [HomeController::class, 'show'])->name('match.show');
Route::get('/league/{league_slug}', [HomeController::class, 'league'])->name('league');
Route::get('/team/{slug}', [HomeController::class, 'team'])->name('team');
Route::get('/search', [HomeController::class, 'search'])->name('search');

// Localized routes
Route::prefix('{locale}')
    ->where(['locale' => 'es|pt|ar|id|ja|fr|de|tr|hi'])
    ->group(function () {
        Route::get('/', [HomeController::class, 'index'])->name('home.locale');
        Route::get('/matches', [HomeController::class, 'matches'])->name('matches.locale');
        Route::get('/match/{slug}', [HomeController::class, 'show'])->name('match.show.locale');
        Route::get('/league/{league_slug}', [HomeController::class, 'league'])->name('league.locale');
        Route::get('/team/{slug}', [HomeController::class, 'team'])->name('team.locale');
        Route::get('/search', [HomeController::class, 'search'])->name('search.locale');
    });
