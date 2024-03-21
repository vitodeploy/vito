<?php

use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    require __DIR__.'/settings.php';

    Route::prefix('/servers')->group(function () {
        require __DIR__.'/server.php';
    });

    Route::get('/search', [SearchController::class, 'search'])->name('search');
});
