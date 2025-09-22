<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // ========================================
        // Server Backups
        // ========================================
        $schedule->command('backups:run "0 * * * *"')->hourly();
        $schedule->command('backups:run "0 0 * * *"')->daily();
        $schedule->command('backups:run "0 0 * * 0"')->weekly();
        $schedule->command('backups:run "0 0 1 * *"')->monthly();

        // ========================================
        // Vito Backups
        // ========================================
        $schedule->command('vito-backups:run "0 * * * *"')->hourly();
        $schedule->command('vito-backups:run "0 0 * * *"')->daily();
        $schedule->command('vito-backups:run "0 0 * * 0"')->weekly();
        $schedule->command('vito-backups:run "0 0 1 * *"')->monthly();

        // ========================================
        // System Monitoring & Maintenance
        // ========================================
        $schedule->command('metrics:delete-older-metrics')->daily();
        $schedule->command('metrics:get')->everyMinute();
        $schedule->command('servers:check')->everyFiveMinutes();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
