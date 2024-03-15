<?php

use App\Http\Controllers\Settings\NotificationChannelController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\ProjectController;
use App\Http\Controllers\Settings\ServerProviderController;
use App\Http\Controllers\Settings\SourceControlController;
use App\Http\Controllers\Settings\SSHKeyController;
use App\Http\Controllers\Settings\StorageProviderController;
use Illuminate\Support\Facades\Route;

// profile
Route::prefix('settings/profile')->group(function () {
    Route::get('/', [ProfileController::class, 'index'])->name('profile');
    Route::post('info', [ProfileController::class, 'info'])->name('profile.info');
    Route::post('password', [ProfileController::class, 'password'])->name('profile.password');
});

// profile
Route::prefix('settings/projects')->group(function () {
    Route::get('/', [ProjectController::class, 'index'])->name('projects');
    Route::post('create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('update/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('delete/{project}', [ProjectController::class, 'delete'])->name('projects.delete');
    Route::get('switch/{project}', [ProjectController::class, 'switch'])->name('projects.switch');
});

// server-providers
Route::prefix('settings/server-providers')->group(function () {
    Route::get('/', [ServerProviderController::class, 'index'])->name('server-providers');
    Route::post('connect', [ServerProviderController::class, 'connect'])->name('server-providers.connect');
    Route::delete('delete/{serverProvider}', [ServerProviderController::class, 'delete'])
        ->name('server-providers.delete');
});

// source-controls
Route::prefix('settings/source-controls')->group(function () {
    Route::get('/', [SourceControlController::class, 'index'])->name('source-controls');
    Route::post('connect', [SourceControlController::class, 'connect'])->name('source-controls.connect');
    Route::delete('delete/{sourceControl}', [SourceControlController::class, 'delete'])
        ->name('source-controls.delete');
});

// storage-providers
Route::prefix('settings/storage-providers')->group(function () {
    Route::get('/', [StorageProviderController::class, 'index'])->name('storage-providers');
    Route::post('connect', [StorageProviderController::class, 'connect'])->name('storage-providers.connect');
    Route::delete('delete/{storageProvider}', [StorageProviderController::class, 'delete'])
        ->name('storage-providers.delete');
});

// notification-channels
Route::prefix('settings/notification-channels')->group(function () {
    Route::get('/', [NotificationChannelController::class, 'index'])->name('notification-channels');
    Route::post('add', [NotificationChannelController::class, 'add'])
        ->name('notification-channels.add');
    Route::delete('delete/{id}', [NotificationChannelController::class, 'delete'])
        ->name('notification-channels.delete');
});

// ssh-keys
Route::prefix('settings/ssh-keys')->group(function () {
    Route::get('/', [SSHKeyController::class, 'index'])->name('ssh-keys');
    Route::post('add', [SshKeyController::class, 'add'])->name('ssh-keys.add');
    Route::delete('delete/{id}', [SshKeyController::class, 'delete'])->name('ssh-keys.delete');
});
