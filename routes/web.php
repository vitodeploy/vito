<?php

use App\Http\Controllers\CronjobController;
use App\Http\Controllers\DaemonController;
use App\Http\Controllers\DatabaseController;
use App\Http\Controllers\FirewallController;
use App\Http\Controllers\PHPController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\ServerSettingController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\SSHKeyController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::prefix('/settings')->group(function () {
        Route::view('/profile', 'profile.index')->name('profile');
        Route::view('/server-providers', 'server-providers.index')->name('server-providers');
        Route::view('/source-controls', 'source-controls.index')->name('source-controls');
        Route::view('/notification-channels', 'notification-channels.index')->name('notification-channels');
        Route::view('/ssh-keys', 'ssh-keys.index')->name('ssh-keys');
    });
    Route::prefix('/servers')->group(function () {
        Route::get('/', [ServerController::class, 'index'])->name('servers');
        Route::get('/create', [ServerController::class, 'create'])->name('servers.create');
        Route::get('/{server}', [ServerController::class, 'show'])->name('servers.show');
        Route::get('/{server}/logs', [ServerController::class, 'logs'])->name('servers.logs');
        Route::get('/{server}/settings', [ServerSettingController::class, 'index'])->name('servers.settings');
        Route::get('/{server}/databases', [DatabaseController::class, 'index'])->name('servers.databases');
        Route::prefix('/{server}/sites')->group(function () {
            Route::get('/', [SiteController::class, 'index'])->name('servers.sites');
            Route::get('/create', [SiteController::class, 'create'])->name('servers.sites.create');
            Route::get('/{site}', [SiteController::class, 'show'])->name('servers.sites.show');
            Route::get('/{site}/application', [SiteController::class, 'application'])->name('servers.sites.application');
            Route::get('/{site}/ssl', [SiteController::class, 'ssl'])->name('servers.sites.ssl');
            Route::get('/{site}/queues', [SiteController::class, 'queues'])->name('servers.sites.queues');
            Route::get('/{site}/settings', [SiteController::class, 'settings'])->name('servers.sites.settings');
            Route::get('/{site}/logs', [SiteController::class, 'logs'])->name('servers.sites.logs');
        });
        Route::get('/{server}/php', [PHPController::class, 'index'])->name('servers.php');
        Route::get('/{server}/firewall', [FirewallController::class, 'index'])->name('servers.firewall');
        Route::get('/{server}/cronjobs', [CronjobController::class, 'index'])->name('servers.cronjobs');
        Route::get('/{server}/daemons', [DaemonController::class, 'index'])->name('servers.daemons');
        Route::get('/{server}/services', [ServiceController::class, 'index'])->name('servers.services');
        Route::get('/{server}/ssh-keys', [SSHKeyController::class, 'index'])->name('servers.ssh-keys');
    });
});

require __DIR__.'/auth.php';
