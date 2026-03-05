<?php

namespace App\Jobs\Backup;

use App\Enums\BackupFileStatus;
use App\Models\BackupFile;
use App\Models\Database;
use App\Models\ServerLog;
use App\Models\Service;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RestoreDatabaseJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(
        protected BackupFile $backupFile,
        protected Database $database,
    ) {}

    public function handle(): void
    {
        $this->run("database-{$this->database->id}", function () {
            /** @var Service $service */
            $service = $this->database->server->database();
            /** @var \App\Services\Database\Database $databaseHandler */
            $databaseHandler = $service->handler();
            $databaseHandler->restoreBackup($this->backupFile, $this->database->name);
            $this->backupFile->status = BackupFileStatus::RESTORED;
            $this->backupFile->restored_at = now();
            $this->backupFile->save();
        });
    }

    public function failed(Exception $e): void
    {
        $this->backupFile->status = BackupFileStatus::RESTORE_FAILED;
        $this->backupFile->save();
        ServerLog::log($this->database->server, 'restore-database-failed', $e->getMessage());
    }
}
