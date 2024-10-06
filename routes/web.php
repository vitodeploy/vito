<?php

use App\Http\Controllers\ConsoleController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::post('/{server}/console', [ConsoleController::class, 'run'])->name('servers.console.run');
});
