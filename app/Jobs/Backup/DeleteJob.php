<?php

namespace App\Jobs\Backup;

use App\Enums\BackupFileStatus;
use App\Models\Backup;
use App\Models\ServerLog;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeleteJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(protected Backup $backup) {}

    public function handle(): void
    {
        $this->run("backup-{$this->backup->id}", function () {
            $files = $this->backup->files;
            foreach ($files as $file) {
                $file->status = BackupFileStatus::DELETING;
                $file->save();

                $file->deleteFile();
            }

            $this->backup->delete();
        });
    }

    public function failed(Exception $e): void
    {
        ServerLog::log($this->backup->server, 'delete-backup-failed', $e->getMessage());
    }
}
