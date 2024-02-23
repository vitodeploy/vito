<?php

// git hook
use App\Http\Controllers\GitHookController;
use Illuminate\Support\Facades\Route;

Route::any('git-hooks', GitHookController::class)->name('git-hooks');
