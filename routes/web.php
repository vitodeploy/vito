<?php

use App\Http\Controllers\ConsoleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::middleware(['server-is-ready'])->group(function () {
        Route::post('/{server}/console', [ConsoleController::class, 'run'])->name('servers.console.run');
    });
});
