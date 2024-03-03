<?php

use App\Http\Controllers\SiteController;
use App\Http\Controllers\SSHKeyController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    require __DIR__.'/settings.php';

    Route::prefix('/servers')->group(function () {
        require __DIR__.'/server.php';

        Route::middleware('server-is-ready')->group(function () {
            Route::prefix('/{server}/sites')->group(function () {
                Route::get('/', [SiteController::class, 'index'])->name('servers.sites');
                Route::get('/create', [SiteController::class, 'create'])->name('servers.sites.create');
                Route::get('/{site}', [SiteController::class, 'show'])->name('servers.sites.show');
                Route::get('/{site}/ssl', [SiteController::class, 'ssl'])->name('servers.sites.ssl');
                Route::get('/{site}/queues', [SiteController::class, 'queues'])->name('servers.sites.queues');
                Route::get('/{site}/settings', [SiteController::class, 'settings'])->name('servers.sites.settings');
                Route::get('/{site}/logs', [SiteController::class, 'logs'])->name('servers.sites.logs');
            });
            Route::get('/{server}/ssh-keys', [SSHKeyController::class, 'index'])->name('servers.ssh-keys');
        });
    });
});
