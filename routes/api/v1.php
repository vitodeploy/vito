<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1;

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
    Route::get('/', [V1\UserController::class, 'all'])->name('api-v1.users.all');
    Route::post('/', [V1\UserController::class, 'create'])->name('api-v1.users.create');
    Route::get('/{user}', [V1\UserController::class, 'get'])->name('api-v1.users.get');
    Route::put('/{user}', [V1\UserController::class, 'update'])->name('api-v1.users.update');
    Route::delete('/{user}', [V1\UserController::class, 'delete'])->name('api-v1.users.delete');
});

// route group for servers
Route::prefix('servers')->group(function () {
    Route::get('/', [V1\ServerController::class, 'all'])->name('api-v1.servers.all');
});

// route group for sites
Route::prefix('sites')->group(function () {
    Route::get('/', [V1\SitesController::class, 'all'])->name('api-v1.sites.all');
});