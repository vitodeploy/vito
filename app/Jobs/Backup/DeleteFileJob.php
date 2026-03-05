<?php

namespace App\Jobs\Backup;

use App\Models\BackupFile;
use App\Models\ServerLog;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeleteFileJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(protected BackupFile $file) {}

    public function handle(): void
    {
        $this->run("backup-file-{$this->file->id}", function () {
            $this->file->deleteFile();
        });
    }

    public function failed(Exception $e): void
    {
        ServerLog::log($this->file->backup->server, 'delete-backup-file-failed', $e->getMessage());
    }
}
