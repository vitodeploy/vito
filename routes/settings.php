<?php

use App\Http\Controllers\Settings\NotificationChannelController;
use App\Http\Controllers\Settings\ProjectController;
use App\Http\Controllers\Settings\ServerProviderController;
use App\Http\Controllers\Settings\SourceControlController;
use App\Http\Controllers\Settings\SSHKeyController;
use App\Http\Controllers\Settings\StorageProviderController;
use App\Http\Controllers\Settings\TagController;
use App\Http\Controllers\Settings\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('settings/users')->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('settings.users.index');
    Route::post('/', [UserController::class, 'store'])->name('settings.users.store');
    Route::get('/{user}', [UserController::class, 'show'])->name('settings.users.show');
    Route::post('/{user}', [UserController::class, 'update'])->name('settings.users.update');
    Route::post('/{user}/projects', [UserController::class, 'updateProjects'])->name('settings.users.update-projects');
    Route::delete('/{user}', [UserController::class, 'destroy'])->name('settings.users.delete');
});

// projects
Route::prefix('settings/projects')->group(function () {
    Route::get('/', [ProjectController::class, 'index'])->name('settings.projects');
    Route::post('create', [ProjectController::class, 'create'])->name('settings.projects.create');
    Route::post('update/{project}', [ProjectController::class, 'update'])->name('settings.projects.update');
    Route::delete('delete/{project}', [ProjectController::class, 'delete'])->name('settings.projects.delete');
});

// server-providers
Route::prefix('settings/server-providers')->group(function () {
    Route::get('/', [ServerProviderController::class, 'index'])->name('settings.server-providers');
    Route::post('connect', [ServerProviderController::class, 'connect'])->name('settings.server-providers.connect');
    Route::delete('delete/{serverProvider}', [ServerProviderController::class, 'delete'])->name('settings.server-providers.delete');
    Route::post('edit/{serverProvider}', [ServerProviderController::class, 'update'])->name('settings.server-providers.update');
});

// source-controls
Route::prefix('settings/source-controls')->group(function () {
    Route::get('/', [SourceControlController::class, 'index'])->name('settings.source-controls');
    Route::post('connect', [SourceControlController::class, 'connect'])->name('settings.source-controls.connect');
    Route::delete('delete/{sourceControl}', [SourceControlController::class, 'delete'])->name('settings.source-controls.delete');
    Route::post('edit/{sourceControl}', [SourceControlController::class, 'update'])->name('settings.source-controls.update');
});

// storage-providers
Route::prefix('settings/storage-providers')->group(function () {
    Route::get('/', [StorageProviderController::class, 'index'])->name('settings.storage-providers');
    Route::post('connect', [StorageProviderController::class, 'connect'])->name('settings.storage-providers.connect');
    Route::delete('delete/{storageProvider}', [StorageProviderController::class, 'delete'])->name('settings.storage-providers.delete');
    Route::post('edit/{storageProvider}', [StorageProviderController::class, 'update'])->name('settings.storage-providers.update');
});

// notification-channels
Route::prefix('settings/notification-channels')->group(function () {
    Route::get('/', [NotificationChannelController::class, 'index'])->name('settings.notification-channels');
    Route::post('add', [NotificationChannelController::class, 'add'])->name('settings.notification-channels.add');
    Route::delete('delete/{id}', [NotificationChannelController::class, 'delete'])->name('settings.notification-channels.delete');
    Route::post('edit/{notificationChannel}', [NotificationChannelController::class, 'update'])->name('settings.notification-channels.update');
});

// ssh-keys
Route::prefix('settings/ssh-keys')->group(function () {
    Route::get('/', [SSHKeyController::class, 'index'])->name('settings.ssh-keys');
    Route::post('add', [SshKeyController::class, 'add'])->name('settings.ssh-keys.add');
    Route::delete('delete/{id}', [SshKeyController::class, 'delete'])->name('settings.ssh-keys.delete');
});

// tags
Route::prefix('/tags')->group(function () {
    Route::get('/', [TagController::class, 'index'])->name('settings.tags');
    Route::post('/create', [TagController::class, 'create'])->name('settings.tags.create');
    Route::post('/{tag}', [TagController::class, 'update'])->name('settings.tags.update');
    Route::delete('/{tag}', [TagController::class, 'delete'])->name('settings.tags.delete');
});
