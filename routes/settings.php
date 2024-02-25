<?php

use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\ProjectController;
use App\Http\Controllers\Settings\ServerProviderController;
use App\Http\Controllers\Settings\SourceControlController;
use Illuminate\Support\Facades\Route;

// profile
Route::middleware('auth')->prefix('settings/profile')->group(function () {
    Route::get('/', [ProfileController::class, 'index'])->name('profile');
    Route::post('info', [ProfileController::class, 'info'])->name('profile.info');
    Route::post('password', [ProfileController::class, 'password'])->name('profile.password');
});

// profile
Route::middleware('auth')->prefix('settings/projects')->group(function () {
    Route::get('/', [ProjectController::class, 'index'])->name('projects');
    Route::post('create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('update/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('delete/{project}', [ProjectController::class, 'delete'])->name('projects.delete');
    Route::get('switch/{project}', [ProjectController::class, 'switch'])->name('projects.switch');
});

// server-providers
Route::middleware('auth')->prefix('settings/server-providers')->group(function () {
    Route::get('/', [ServerProviderController::class, 'index'])->name('server-providers');
    Route::post('connect', [ServerProviderController::class, 'connect'])->name('server-providers.connect');
    Route::delete('delete/{id}', [ServerProviderController::class, 'delete'])
        ->name('server-providers.delete');
});

// source-controls
Route::middleware('auth')->prefix('settings/source-controls')->group(function () {
    Route::get('/', [SourceControlController::class, 'index'])->name('source-controls');
    Route::post('connect', [SourceControlController::class, 'connect'])->name('source-controls.connect');
    Route::delete('delete/{id}', [SourceControlController::class, 'delete'])
        ->name('source-controls.delete');
});
