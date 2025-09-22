<?php

namespace App\Console\Commands;

use App\Actions\VitoBackup\RunVitoBackup;
use App\Enums\VitoBackupStatus;
use App\Models\VitoBackup;
use Illuminate\Console\Command;

class RunVitoBackupsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vito-backups:run {frequency?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run Vito backups for a specific frequency or all active backups';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $frequency = $this->argument('frequency');

        $query = VitoBackup::where('status', VitoBackupStatus::PENDING);

        if ($frequency) {
            $query->where('frequency', $frequency);
        }

        $backups = $query->get();

        if ($backups->isEmpty()) {
            $this->info('No backups to run.');

            return;
        }

        $this->info("Running {$backups->count()} backup(s)...");

        foreach ($backups as $backup) {
            $this->info("Running backup: {$backup->name}");

            try {
                app(RunVitoBackup::class)->run($backup);
                $this->info("✓ Backup '{$backup->name}' completed successfully.");
            } catch (\Exception $e) {
                $this->error("✗ Backup '{$backup->name}' failed: ".$e->getMessage());
            }
        }

        $this->info('All backups processed.');
    }
}
