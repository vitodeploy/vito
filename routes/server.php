<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\ConsoleController;
use App\Http\Controllers\CronjobController;
use App\Http\Controllers\DatabaseBackupController;
use App\Http\Controllers\DatabaseController;
use App\Http\Controllers\DatabaseUserController;
use App\Http\Controllers\FirewallController;
use App\Http\Controllers\MetricController;
use App\Http\Controllers\PHPController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\ServerLogController;
use App\Http\Controllers\ServerSettingController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\SiteLogController;
use App\Http\Controllers\SiteSettingController;
use App\Http\Controllers\SSHKeyController;
use App\Http\Controllers\SSLController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ServerController::class, 'index'])->name('servers');
Route::get('/create', [ServerController::class, 'create'])->name('servers.create');
Route::post('/create', [ServerController::class, 'store']);

Route::middleware('select-current-project')->group(function () {
    Route::get('/{server}', [ServerController::class, 'show'])->name('servers.show');
    Route::delete('/{server}', [ServerController::class, 'delete'])->name('servers.delete');

    Route::middleware(['server-is-ready', 'handle-ssh-errors'])->group(function () {
        Route::prefix('/{server}/sites')->group(function () {
            // sites
            Route::get('/', [SiteController::class, 'index'])->name('servers.sites');
            Route::get('/create', [SiteController::class, 'create'])->name('servers.sites.create');
            Route::post('/create', [SiteController::class, 'store']);
            Route::get('/{site}', [SiteController::class, 'show'])->name('servers.sites.show');
            Route::delete('/{site}', [SiteController::class, 'destroy'])->name('servers.sites.destroy');
            Route::get('/{site}/installing', [SiteController::class, 'installing'])->name('servers.sites.installing');

            // site application
            Route::post('/{site}/application/deploy', [ApplicationController::class, 'deploy'])->name('servers.sites.application.deploy');
            Route::get('/{site}/application/{deployment}/log', [ApplicationController::class, 'showDeploymentLog'])->name('servers.sites.application.deployment.log');
            Route::post('/{site}/application/deployment-script', [ApplicationController::class, 'updateDeploymentScript'])->name('servers.sites.application.deployment-script');
            Route::post('/{site}/application/branch', [ApplicationController::class, 'updateBranch'])->name('servers.sites.application.branch');
            Route::get('/{site}/application/env', [ApplicationController::class, 'getEnv'])->name('servers.sites.application.env');
            Route::post('/{site}/application/env', [ApplicationController::class, 'updateEnv']);
            Route::post('/{site}/application/auto-deployment', [ApplicationController::class, 'enableAutoDeployment'])->name('servers.sites.application.auto-deployment');
            Route::delete('/{site}/application/auto-deployment', [ApplicationController::class, 'disableAutoDeployment']);

            // site ssl
            Route::get('/{site}/ssl', [SSLController::class, 'index'])->name('servers.sites.ssl');
            Route::post('/{site}/ssl', [SSLController::class, 'store'])->name('servers.sites.ssl.store');
            Route::delete('/{site}/ssl/{ssl}', [SSLController::class, 'destroy'])->name('servers.sites.ssl.destroy');

            // site queues
            Route::get('/{site}/queues', [QueueController::class, 'index'])->name('servers.sites.queues');
            Route::post('/{site}/queues', [QueueController::class, 'store'])->name('servers.sites.queues.store');
            Route::post('/{site}/queues/{queue}/action/{action}', [QueueController::class, 'action'])->name('servers.sites.queues.action');
            Route::delete('/{site}/queues/{queue}', [QueueController::class, 'destroy'])->name('servers.sites.queues.destroy');
            Route::get('/{site}/queues/{queue}/logs', [QueueController::class, 'logs'])->name('servers.sites.queues.logs');

            // site settings
            Route::get('/{site}/settings', [SiteSettingController::class, 'index'])->name('servers.sites.settings');
            Route::get('/{site}/settings/vhost', [SiteSettingController::class, 'getVhost'])->name('servers.sites.settings.vhost');
            Route::post('/{site}/settings/vhost', [SiteSettingController::class, 'updateVhost']);
            Route::post('/{site}/settings/php', [SiteSettingController::class, 'updatePHPVersion'])->name('servers.sites.settings.php');
            Route::post('/{site}/settings/source-control', [SiteSettingController::class, 'updateSourceControl'])->name('servers.sites.settings.source-control');
            Route::post('/{site}/settings/update-aliases', [SiteSettingController::class, 'updateAliases'])->name('servers.sites.settings.aliases');

            // site logs
            Route::get('/{site}/logs', [SiteLogController::class, 'index'])->name('servers.sites.logs');
        });

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
        Route::post('/{server}/cronjobs/{cronJob}/enable', [CronjobController::class, 'enable'])->name('servers.cronjobs.enable');
        Route::post('/{server}/cronjobs/{cronJob}/disable', [CronjobController::class, 'disable'])->name('servers.cronjobs.disable');

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
        Route::get('/{server}/services/{service}/enable', [ServiceController::class, 'enable'])->name('servers.services.enable');
        Route::get('/{server}/services/{service}/disable', [ServiceController::class, 'disable'])->name('servers.services.disable');
        Route::post('/{server}/services/install', [ServiceController::class, 'install'])->name('servers.services.install');
        Route::delete('/{server}/services/{service}/uninstall', [ServiceController::class, 'uninstall'])->name('servers.services.uninstall');

        // metrics
        Route::get('/{server}/metrics', [MetricController::class, 'index'])->name('servers.metrics');
        Route::post('/{server}/metrics/settings', [MetricController::class, 'settings'])->name('servers.metrics.settings');

        // console
        Route::get('/{server}/console', [ConsoleController::class, 'index'])->name('servers.console');
        Route::post('/{server}/console', [ConsoleController::class, 'run'])->name('servers.console.run');
    });

    // settings
    Route::prefix('/{server}/settings')->group(function () {
        Route::get('/', [ServerSettingController::class, 'index'])->name('servers.settings');
        Route::post('/check-connection', [ServerSettingController::class, 'checkConnection'])->name('servers.settings.check-connection');
        Route::post('/reboot', [ServerSettingController::class, 'reboot'])->name('servers.settings.reboot');
        Route::post('/edit', [ServerSettingController::class, 'edit'])->name('servers.settings.edit');
        Route::post('/check-updates', [ServerSettingController::class, 'checkUpdates'])->name('servers.settings.check-updates');
        Route::post('/update', [ServerSettingController::class, 'update'])->name('servers.settings.update');
    });

    // logs
    Route::prefix('/{server}/logs')->group(function () {
        Route::get('/', [ServerLogController::class, 'index'])->name('servers.logs');
        Route::get('/remote', [ServerLogController::class, 'remote'])->name('servers.logs.remote');
        Route::post('/remote', [ServerLogController::class, 'store'])->name('servers.logs.remote.store');
        Route::delete('/remote/{serverLog}', [ServerLogController::class, 'destroy'])->name('servers.logs.remote.destroy');
        Route::get('/{serverLog}', [ServerLogController::class, 'show'])->name('servers.logs.show');
    });
});
