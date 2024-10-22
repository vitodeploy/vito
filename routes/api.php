<?php

// git hook

use App\Http\Controllers\API\AgentController;
use App\Http\Controllers\API\GitHookController;
use App\Http\Controllers\API\HealthController;
use App\Http\Controllers\API\ProjectController;
use App\Http\Controllers\API\ServerController;
use App\Http\Controllers\API\ServerProviderController;
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

    // servers
    Route::group(['prefix' => 'projects/{project}/servers'], function () {
        Route::middleware('ability:read')->group(function () {
            Route::get('/', [ServerController::class, 'index'])->name('api.servers.index');
            Route::get('/{server}', [ServerController::class, 'show'])->name('api.servers.show');
        });
        Route::middleware('ability:write')->group(function () {
            Route::post('/', [ServerController::class, 'store'])->name('api.servers.store');
        });
    });

    // server providers
    Route::group(['prefix' => 'projects/{project}/server-providers'], function () {
        Route::middleware('ability:read')->group(function () {
            Route::get('/', [ServerProviderController::class, 'index'])->name('api.server-providers');
            Route::get('/{serverProvider}', [ServerProviderController::class, 'show'])->name('api.server-providers.show');
        });
        Route::middleware('ability:write')->group(function () {
            Route::post('/', [ServerProviderController::class, 'create'])->name('api.server-providers.create');
            Route::delete('/{serverProvider}', [ServerProviderController::class, 'delete'])->name('api.server-providers.delete');
        });
    });
});
