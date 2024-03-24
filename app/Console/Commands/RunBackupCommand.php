<?php

namespace App\Console\Commands;

use App\Actions\Database\RunBackup;
use App\Enums\BackupStatus;
use App\Models\Backup;
use Illuminate\Console\Command;

class RunBackupCommand extends Command
{
    protected $signature = 'backups:run {interval}';

    protected $description = 'Run backup';

    public function handle(): void
    {
        $total = 0;

        Backup::query()
            ->where('interval', $this->argument('interval'))
            ->where('status', BackupStatus::RUNNING)
            ->chunk(100, function ($backups) use (&$total) {
                /** @var Backup $backup */
                foreach ($backups as $backup) {
                    app(RunBackup::class)->run($backup);
                    $total++;
                }
            });

        $this->info("{$total} backups started");
    }
}
