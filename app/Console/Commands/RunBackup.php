<?php

namespace App\Console\Commands;

use App\Models\Backup;
use Illuminate\Console\Command;

class RunBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backups:run {interval}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run backup';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Backup::query()
            ->where('interval', $this->argument('interval'))
            ->where('status', 'running')
            ->chunk(100, function ($backups) {
                /** @var Backup $backup */
                foreach ($backups as $backup) {
                    app(\App\Actions\Database\RunBackup::class)->run($backup);
                }
            });
    }
}
