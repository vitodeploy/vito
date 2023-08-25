<?php

namespace App\Jobs\Backup;

use App\Enums\BackupFileStatus;
use App\Events\Broadcast;
use App\Jobs\Job;
use App\Models\BackupFile;

class RunBackup extends Job
{
    protected BackupFile $backupFile;

    public function __construct(BackupFile $backupFile)
    {
        $this->backupFile = $backupFile;
    }

    public function handle(): void
    {
        if ($this->backupFile->backup->type === 'database') {
            $this->backupFile->backup->server->database()->handler()->runBackup($this->backupFile);
        }

        $this->backupFile->status = BackupFileStatus::CREATED;
        $this->backupFile->save();

        event(
            new Broadcast('run-backup-finished', [
                'file' => $this->backupFile,
            ])
        );
    }

    public function failed(): void
    {
        $this->backupFile->status = BackupFileStatus::FAILED;
        $this->backupFile->save();
        event(
            new Broadcast('run-backup-failed', [
                'file' => $this->backupFile,
            ])
        );
    }
}
