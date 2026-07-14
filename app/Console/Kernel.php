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
        $schedule->command('backups:run')->everyMinute();
        $schedule->command('backups:reconcile')->everyThirtyMinutes();
        $schedule->command('metrics:delete-older-metrics')->daily();
        $schedule->command('db:vacuum')->daily();
        $schedule->command('metrics:get')->everyMinute()->withoutOverlapping(5);
        $schedule->command('servers:check')->everyFiveMinutes();
        $schedule->command('servers:check-updates')->dailyAt('02:00');
        $schedule->command('servers:auto-update')->everyMinute()->withoutOverlapping();
        $schedule->command('domains:check-pending')->everyFiveMinutes();
        $schedule->command('ssl:renew-wildcards')->daily();
        $schedule->command('ssl:check-expiry')->daily();
        $schedule->command('github-app:sync')->cron('0 */4 * * *');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
