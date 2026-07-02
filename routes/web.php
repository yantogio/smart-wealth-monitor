<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DcaController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\WatchlistController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('dashboard');
Route::get('/watchlist', [WatchlistController::class, 'index'])->name('watchlist.index');

Route::get('/dca', [DcaController::class, 'index'])->name('dca.index');
Route::post('/dca', [DcaController::class, 'simulate'])->name('dca.simulate');

Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
Route::post('/settings/api-key', [SettingsController::class, 'updateApiKey'])->name('settings.api-key.update');
Route::post('/settings/force-sync', [SettingsController::class, 'forceSync'])->name('settings.force-sync');
