<?php

namespace App\Jobs\Backup;

use App\Enums\BackupFileStatus;
use App\Events\Broadcast;
use App\Jobs\Job;
use App\Models\BackupFile;
use App\Models\Database;

class RestoreDatabase extends Job
{
    protected BackupFile $backupFile;

    protected Database $database;

    public function __construct(BackupFile $backupFile, Database $database)
    {
        $this->backupFile = $backupFile;
        $this->database = $database;
    }

    public function handle(): void
    {
        $this->database->server->database()->handler()->restoreBackup($this->backupFile, $this->database->name);

        $this->backupFile->status = BackupFileStatus::RESTORED;
        $this->backupFile->restored_at = now();
        $this->backupFile->save();

        event(
            new Broadcast('backup-restore-finished', [
                'file' => $this->backupFile,
            ])
        );
    }

    public function failed(): void
    {
        $this->backupFile->status = BackupFileStatus::RESTORE_FAILED;
        $this->backupFile->save();
        event(
            new Broadcast('backup-restore-failed', [
                'file' => $this->backupFile,
            ])
        );
    }
}
