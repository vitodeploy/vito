<?php

use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;

// profile
Route::middleware('auth')->prefix('settings/profile')->group(function () {
    Route::get('/', [ProfileController::class, 'index'])->name('profile');
    Route::post('info', [ProfileController::class, 'info'])->name('profile.info');
    Route::post('password', [ProfileController::class, 'password'])->name('profile.password');
});
