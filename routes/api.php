<?php

// git hook

use App\Http\Controllers\API\AgentController;
use App\Http\Controllers\API\GitHookController;
use App\Http\Controllers\API\HealthController;
use App\Http\Controllers\API\ProjectController;
use App\Http\Controllers\API\ServerController;
use App\Http\Controllers\API\ServerProviderController;
use App\Http\Controllers\API\SourceControlController;
use App\Http\Controllers\API\StorageProviderController;
use Illuminate\Support\Facades\Route;

Route::get('health', HealthController::class)->name('api.health');
Route::any('git-hooks', GitHookController::class)->name('api.git-hooks');

Route::post('servers/{server}/agent/{id}', AgentController::class)->name('api.servers.agent');

Route::middleware('auth:sanctum')->group(function () {
    // projects
    Route::group(['prefix' => 'projects'], function () {
        Route::middleware('ability:read')->group(function () {
            Route::get('/', [ProjectController::class, 'index'])->name('api.projects.index');
            Route::get('/{project}', [ProjectController::class, 'show'])->name('api.projects.show');
        });
        Route::middleware('ability:write')->group(function () {
            Route::post('/', [ProjectController::class, 'store'])->name('api.projects.store');
            Route::put('/{project}', [ProjectController::class, 'update'])->name('api.projects.update');
            Route::delete('/{project}', [ProjectController::class, 'delete'])->name('api.projects.delete');
        });
    });

    Route::group(['prefix' => 'projects/{project}'], function () {
        // servers
        Route::group(['prefix' => '/servers'], function () {
            Route::middleware('ability:read')->group(function () {
                Route::get('/', [ServerController::class, 'index'])->name('api.servers');
                Route::get('/{server}', [ServerController::class, 'show'])->name('api.servers.show');
            });
            Route::middleware('ability:write')->group(function () {
                Route::post('/', [ServerController::class, 'create'])->name('api.servers.create');
                Route::post('/{server}/reboot', [ServerController::class, 'reboot'])->name('api.servers.reboot');
                Route::post('/{server}/upgrade', [ServerController::class, 'upgrade'])->name('api.servers.upgrade');
                Route::delete('/{server}', [ServerController::class, 'delete'])->name('api.servers.delete');
            });
        });

        // server providers
        Route::group(['prefix' => '/server-providers'], function () {
            Route::middleware('ability:read')->group(function () {
                Route::get('/', [ServerProviderController::class, 'index'])->name('api.server-providers');
                Route::get('/{serverProvider}', [ServerProviderController::class, 'show'])->name('api.server-providers.show');
            });
            Route::middleware('ability:write')->group(function () {
                Route::post('/', [ServerProviderController::class, 'create'])->name('api.server-providers.create');
                Route::delete('/{serverProvider}', [ServerProviderController::class, 'delete'])->name('api.server-providers.delete');
            });
        });

        // storage providers
        Route::group(['prefix' => '/storage-providers'], function () {
            Route::middleware('ability:read')->group(function () {
                Route::get('/', [StorageProviderController::class, 'index'])->name('api.storage-providers');
                Route::get('/{storageProvider}', [StorageProviderController::class, 'show'])->name('api.storage-providers.show');
            });
            Route::middleware('ability:write')->group(function () {
                Route::post('/', [StorageProviderController::class, 'create'])->name('api.storage-providers.create');
                Route::delete('/{storageProvider}', [StorageProviderController::class, 'delete'])->name('api.storage-providers.delete');
            });
        });

        // source controls
        Route::group(['prefix' => '/source-controls'], function () {
            Route::middleware('ability:read')->group(function () {
                Route::get('/', [SourceControlController::class, 'index'])->name('api.source-controls');
                Route::get('/{sourceControl}', [SourceControlController::class, 'show'])->name('api.source-controls.show');
            });
            Route::middleware('ability:write')->group(function () {
                Route::post('/', [SourceControlController::class, 'create'])->name('api.source-controls.create');
                Route::put('/{sourceControl}', [SourceControlController::class, 'update'])->name('api.source-controls.update');
                Route::delete('/{sourceControl}', [SourceControlController::class, 'delete'])->name('api.source-controls.delete');
            });
        });
    })->middleware('can-see-project');
});
