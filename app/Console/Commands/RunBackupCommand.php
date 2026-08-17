<?php

namespace App\Console\Commands;

use App\Actions\Backup\RunBackup;
use App\Models\Backup;
use Cron\CronExpression;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class RunBackupCommand extends Command
{
    protected $signature = 'backups:run';

    protected $description = 'Run backups that are due';

    public function handle(): void
    {
        $total = 0;
        $failed = 0;

        Backup::query()
            ->where('enabled', true)
            ->whereNull('status')
            ->whereHas('server')
            ->with('server')
            ->chunkById(100, function ($backups) use (&$total, &$failed): void {
                /** @var Backup $backup */
                foreach ($backups as $backup) {
                    if (! CronExpression::isValidExpression((string) $backup->interval)) {
                        continue;
                    }

                    if ((new CronExpression((string) $backup->interval))->isDue(now(), config('app.timezone'))) {
                        try {
                            app(RunBackup::class)->run($backup);
                            $total++;
                        } catch (Throwable $e) {
                            Log::warning('Failed to run backup', [
                                'backup_id' => $backup->id,
                                'server_id' => $backup->server_id,
                                'error' => $e->getMessage(),
                            ]);
                            $failed++;
                        }
                    }
                }
            });

        $this->info("{$total} backups started, {$failed} failed");
    }
}
