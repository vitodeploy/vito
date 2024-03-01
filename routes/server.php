<?php

use App\Http\Controllers\ServerController;
use App\Http\Controllers\ServerLogController;
use App\Http\Controllers\ServerSettingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ServerController::class, 'index'])->name('servers');
Route::get('/create', [ServerController::class, 'create'])->name('servers.create');
Route::post('/create', [ServerController::class, 'store'])->name('servers.create');
Route::get('/{server}', [ServerController::class, 'show'])->name('servers.show');
Route::delete('/{server}', [ServerController::class, 'delete'])->name('servers.delete');

// Logs
Route::get('/{server}/logs', [ServerLogController::class, 'index'])->name('servers.logs');
Route::get('/{server}/logs/{serverLog}', [ServerLogController::class, 'show'])->name('servers.logs.show');

// Settings
Route::get('/{server}/settings', [ServerSettingController::class, 'index'])->name('servers.settings');
Route::post('/{server}/settings/check-connection', [ServerSettingController::class, 'checkConnection'])
    ->name('servers.settings.check-connection');
Route::post('/{server}/settings/reboot', [ServerSettingController::class, 'reboot'])
    ->name('servers.settings.reboot');
Route::post('/{server}/settings/edit', [ServerSettingController::class, 'edit'])
    ->name('servers.settings.edit');
