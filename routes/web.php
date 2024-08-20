<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScriptController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Settings\ProjectController;
use App\Http\Controllers\Settings\TagController;
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

    Route::prefix('/settings/tags')->group(function () {
        Route::post('/attach', [TagController::class, 'attach'])->name('tags.attach');
        Route::post('/{tag}/detach', [TagController::class, 'detach'])->name('tags.detach');
    });

    Route::prefix('/servers')->middleware('must-have-current-project')->group(function () {
        require __DIR__.'/server.php';
    });

    Route::prefix('/scripts')->group(function () {
        Route::get('/', [ScriptController::class, 'index'])->name('scripts.index');
        Route::post('/', [ScriptController::class, 'store'])->name('scripts.store');
        Route::get('/{script}', [ScriptController::class, 'show'])->name('scripts.show');
        Route::post('/{script}/edit', [ScriptController::class, 'edit'])->name('scripts.edit');
        Route::post('/{script}/execute', [ScriptController::class, 'execute'])->name('scripts.execute');
        Route::delete('/{script}/delete', [ScriptController::class, 'delete'])->name('scripts.delete');
        Route::get('/{script}/log/{execution}', [ScriptController::class, 'log'])->name('scripts.log');
    });

    Route::get('/search', [SearchController::class, 'search'])->name('search');
});
