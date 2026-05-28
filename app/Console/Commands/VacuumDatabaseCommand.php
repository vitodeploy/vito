<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VacuumDatabaseCommand extends Command
{
    protected $signature = 'db:vacuum';

    protected $description = 'Reclaim unused space in the SQLite database';

    public function handle(): int
    {
        $connection = DB::connection();

        if ($connection->getDriverName() !== 'sqlite') {
            $this->warn('VACUUM is only supported on SQLite connections. Skipping.');

            return self::SUCCESS;
        }

        $database = $connection->getDatabaseName();

        if (! is_file($database)) {
            $this->warn('Could not locate the SQLite database file. Skipping.');

            return self::SUCCESS;
        }

        $databaseSize = filesize($database);

        if ($databaseSize === false) {
            $this->warn('Could not determine the SQLite database file size. Skipping.');

            return self::SUCCESS;
        }

        $required = $databaseSize * 2;
        $available = disk_free_space(dirname($database));

        if ($available === false) {
            $this->warn('Could not determine the available disk space. Skipping.');

            return self::SUCCESS;
        }

        if ($available < $required) {
            $this->warn(sprintf(
                'Not enough free disk space to vacuum safely (need ~%s, have %s). Skipping.',
                $this->formatBytes($required),
                $this->formatBytes((int) $available),
            ));

            return self::SUCCESS;
        }

        $this->info('Vacuuming the database...');

        $connection->statement('VACUUM');

        $this->info('Database vacuumed!');

        return self::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $power = min($power, count($units) - 1);

        return round($bytes / (1024 ** $power), 2).' '.$units[$power];
    }
}
