<?php

namespace App\Console\Commands;

use App\Actions\Backup\RunBackup;
use App\Models\Backup;
use Cron\CronExpression;
use Illuminate\Console\Command;

class RunBackupCommand extends Command
{
    protected $signature = 'backups:run';

    protected $description = 'Run backups that are due';

    public function handle(): void
    {
        $total = 0;

        Backup::query()
            ->where('enabled', true)
            ->whereNull('status')
            ->chunkById(100, function ($backups) use (&$total): void {
                /** @var Backup $backup */
                foreach ($backups as $backup) {
                    if (! CronExpression::isValidExpression((string) $backup->interval)) {
                        continue;
                    }

                    if ((new CronExpression((string) $backup->interval))->isDue(now(), config('app.timezone'))) {
                        app(RunBackup::class)->run($backup);
                        $total++;
                    }
                }
            });

        $this->info("{$total} backups started");
    }
}
