<?php

namespace App\Jobs\Backup;

use App\Actions\Backup\BroadcastBackupUpdate;
use App\DTOs\SocketEventDTO;
use App\Enums\BackupFileStatus;
use App\Events\SocketEvent;
use App\Http\Resources\BackupFileResource;
use App\Models\BackupFile;
use App\Models\ServerLog;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class DeleteFileJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(protected BackupFile $file) {}

    public function handle(): void
    {
        $this->run("backup-file-{$this->file->id}", function () {
            $backup = $this->file->backup;
            $projectId = $backup->server->project_id;
            $fileId = $this->file->id;

            $this->file->deleteFile();

            if ($this->file->exists) {
                SocketEvent::dispatch(new SocketEventDTO(
                    projectId: $projectId,
                    type: 'backup-file.updated',
                    data: new BackupFileResource($this->file),
                ));
            } else {
                SocketEvent::dispatch(new SocketEventDTO(
                    projectId: $projectId,
                    type: 'backup-file.deleted',
                    data: ['id' => $fileId],
                ));
            }

            $freshBackup = $backup->fresh();
            if ($freshBackup) {
                app(BroadcastBackupUpdate::class)->broadcast($freshBackup);
            }
        });
    }

    public function failed(Exception $e): void
    {
        ServerLog::log($this->file->backup->server, 'delete-backup-file-failed', $e->getMessage());

        if ($this->file->exists) {
            $this->file->status = BackupFileStatus::DELETE_FAILED;
            $this->file->message = Str::limit($e->getMessage(), 1000);
            $this->file->save();

            SocketEvent::dispatch(new SocketEventDTO(
                projectId: $this->file->backup->server->project_id,
                type: 'backup-file.updated',
                data: new BackupFileResource($this->file),
            ));

            app(BroadcastBackupUpdate::class)->broadcast($this->file->backup);
        }
    }
}
