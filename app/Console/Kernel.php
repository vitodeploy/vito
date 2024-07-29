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
        $schedule->command('backups:run "0 * * * *"')->hourly();
        $schedule->command('backups:run "0 0 * * *"')->daily();
        $schedule->command('backups:run "0 0 * * 0"')->weekly();
        $schedule->command('backups:run "0 0 1 * *"')->monthly();
        $schedule->command('metrics:delete-older-metrics')->daily();
        $schedule->command('metrics:get')->everyMinute();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
