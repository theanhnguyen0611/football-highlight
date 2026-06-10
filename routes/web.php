<?php

use App\Http\Controllers\Web\HomeController;
use Illuminate\Support\Facades\Route;

// Default EN
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/match/{slug}', [HomeController::class, 'show'])->name('match.show');
Route::get('/league/{league_slug}', [HomeController::class, 'league'])->name('league');
Route::get('/team/{slug}', [HomeController::class, 'team'])->name('team');

// Localized routes
Route::prefix('{locale}')
    ->where(['locale' => 'es|pt|ar|id|bn|ja|fr|de|tr|sw|hi'])
    ->group(function () {
        Route::get('/', [HomeController::class, 'index'])->name('home.locale');
        Route::get('/match/{slug}', [HomeController::class, 'show'])->name('match.show.locale');
        Route::get('/league/{league_slug}', [HomeController::class, 'league'])->name('league.locale');
        Route::get('/team/{slug}', [HomeController::class, 'team'])->name('team.locale');
    });
