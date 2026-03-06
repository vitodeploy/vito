<?php

namespace App\Jobs\Backup;

use App\DTOs\SocketEventDTO;
use App\Events\SocketEvent;
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
            $projectId = $this->file->backup->server->project_id;
            $fileId = $this->file->id;

            $this->file->deleteFile();

            SocketEvent::dispatch(new SocketEventDTO(
                projectId: $projectId,
                type: 'backup-file.deleted',
                data: ['id' => $fileId],
            ));
        });
    }

    public function failed(Exception $e): void
    {
        ServerLog::log($this->file->backup->server, 'delete-backup-file-failed', $e->getMessage());
    }
}
