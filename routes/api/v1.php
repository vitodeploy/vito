<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| V1 API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// route group for users
Route::prefix('users')->group(function () {
    Route::get('/', [V1\UserController::class, 'users'])->name('api-v1.users.all');
});

// route group for servers
Route::prefix('servers')->group(function () {
    Route::get('/', [V1\ServerController::class, 'servers'])->name('api-v1.servers.all');
});

// route group for sites
Route::prefix('sites')->group(function () {
    Route::get('/', [V1\SitesController::class, 'sites'])->name('api-v1.sites.all');
});