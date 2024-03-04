<?php

use App\Http\Controllers\CronjobController;
use App\Http\Controllers\DatabaseBackupController;
use App\Http\Controllers\DatabaseController;
use App\Http\Controllers\DatabaseUserController;
use App\Http\Controllers\FirewallController;
use App\Http\Controllers\PHPController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\ServerLogController;
use App\Http\Controllers\ServerSettingController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SSHKeyController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ServerController::class, 'index'])->name('servers');
Route::get('/create', [ServerController::class, 'create'])->name('servers.create');
Route::post('/create', [ServerController::class, 'store'])->name('servers.create');
Route::get('/{server}', [ServerController::class, 'show'])->name('servers.show');
Route::delete('/{server}', [ServerController::class, 'delete'])->name('servers.delete');

Route::middleware(['server-is-ready', 'handle-ssh-errors'])->group(function () {
    Route::prefix('/{server}/databases')->group(function () {
        // databases
        Route::get('/', [DatabaseController::class, 'index'])->name('servers.databases');
        Route::post('/', [DatabaseController::class, 'store'])->name('servers.databases.store');
        Route::delete('/{database}', [DatabaseController::class, 'destroy'])->name('servers.databases.destroy');

        // database users
        Route::post('/users', [DatabaseUserController::class, 'store'])->name('servers.databases.users.store');
        Route::delete('/users/{databaseUser}', [DatabaseUserController::class, 'destroy'])->name('servers.databases.users.destroy');
        Route::post('/users/{databaseUser}/password', [DatabaseUserController::class, 'password'])->name('servers.databases.users.password');
        Route::post('/users/{databaseUser}/link', [DatabaseUserController::class, 'link'])->name('servers.databases.users.link');

        // database backups
        Route::post('/backups', [DatabaseBackupController::class, 'store'])->name('servers.databases.backups.store');
        Route::delete('/backups/{backup}', [DatabaseBackupController::class, 'destroy'])->name('servers.databases.backups.destroy');

        // database backup files
        Route::get('/backups/{backup}', [DatabaseBackupController::class, 'show'])->name('servers.databases.backups');
        Route::get('/backups/{backup}/run', [DatabaseBackupController::class, 'run'])->name('servers.databases.backups.run');
        Route::post('/backups/{backup}/files/{backupFile}/restore', [DatabaseBackupController::class, 'restore'])->name('servers.databases.backups.files.restore');
        Route::delete('/backups/{backup}/files/{backupFile}', [DatabaseBackupController::class, 'destroyFile'])->name('servers.databases.backups.files.destroy');
    });

    // php
    Route::get('/{server}/php', [PHPController::class, 'index'])->name('servers.php');
    Route::post('/{server}/php/install', [PHPController::class, 'install'])->name('servers.php.install');
    Route::post('/{server}/php/install-extension', [PHPController::class, 'installExtension'])->name('servers.php.install-extension');
    Route::post('/{server}/php/default-cli', [PHPController::class, 'defaultCli'])->name('servers.php.default-cli');
    Route::get('/{server}/php/get-ini', [PHPController::class, 'getIni'])->name('servers.php.get-ini');
    Route::post('/{server}/php/update-ini', [PHPController::class, 'updateIni'])->name('servers.php.update-ini');
    Route::delete('/{server}/php/uninstall', [PHPController::class, 'uninstall'])->name('servers.php.uninstall');

    // firewall
    Route::get('/{server}/firewall', [FirewallController::class, 'index'])->name('servers.firewall');
    Route::post('/{server}/firewall', [FirewallController::class, 'store'])->name('servers.firewall.store');
    Route::delete('/{server}/firewall/{firewallRule}', [FirewallController::class, 'destroy'])->name('servers.firewall.destroy');

    // cronjobs
    Route::get('/{server}/cronjobs', [CronjobController::class, 'index'])->name('servers.cronjobs');
    Route::post('/{server}/cronjobs', [CronjobController::class, 'store'])->name('servers.cronjobs.store');
    Route::delete('/{server}/cronjobs/{cronJob}', [CronjobController::class, 'destroy'])->name('servers.cronjobs.destroy');

    // ssh keys
    Route::get('/{server}/ssh-keys', [SSHKeyController::class, 'index'])->name('servers.ssh-keys');
    Route::post('/{server}/ssh-keys', [SSHKeyController::class, 'store'])->name('servers.ssh-keys.store');
    Route::delete('/{server}/ssh-keys/{sshKey}', [SSHKeyController::class, 'destroy'])->name('servers.ssh-keys.destroy');
    Route::post('/{server}/ssh-keys/deploy', [SSHKeyController::class, 'deploy'])->name('servers.ssh-keys.deploy');

    // services
    Route::get('/{server}/services', [ServiceController::class, 'index'])->name('servers.services');
    Route::get('/{server}/services/{service}/start', [ServiceController::class, 'start'])->name('servers.services.start');
    Route::get('/{server}/services/{service}/stop', [ServiceController::class, 'stop'])->name('servers.services.stop');
    Route::get('/{server}/services/{service}/restart', [ServiceController::class, 'restart'])->name('servers.services.restart');
});

// settings
Route::prefix('/{server}/settings')->group(function () {
    Route::get('/', [ServerSettingController::class, 'index'])->name('servers.settings');
    Route::post('/check-connection', [ServerSettingController::class, 'checkConnection'])->name('servers.settings.check-connection');
    Route::post('/reboot', [ServerSettingController::class, 'reboot'])->name('servers.settings.reboot');
    Route::post('/edit', [ServerSettingController::class, 'edit'])->name('servers.settings.edit');
});

// logs
Route::prefix('/{server}/logs')->group(function () {
    Route::get('/', [ServerLogController::class, 'index'])->name('servers.logs');
    Route::get('/{serverLog}', [ServerLogController::class, 'show'])->name('servers.logs.show');
});
