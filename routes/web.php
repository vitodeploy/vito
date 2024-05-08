<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Settings\ProjectController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    // profile
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'index'])->name('profile');
        Route::post('info', [ProfileController::class, 'info'])->name('profile.info');
        Route::post('password', [ProfileController::class, 'password'])->name('profile.password');
    });

    // switch project
    Route::get('settings/projects/switch/{project}', [ProjectController::class, 'switch'])->name('settings.projects.switch');

    Route::middleware('is-admin')->group(function () {
        require __DIR__.'/settings.php';
    });

    Route::prefix('/servers')->middleware('must-have-current-project')->group(function () {
        require __DIR__.'/server.php';
    });

    Route::get('/search', [SearchController::class, 'search'])->name('search');
});
