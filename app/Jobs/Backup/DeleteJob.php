<?php

namespace App\Jobs\Backup;

use App\DTOs\SocketEventDTO;
use App\Enums\BackupFileStatus;
use App\Enums\BackupStatus;
use App\Events\SocketEvent;
use App\Http\Resources\BackupResource;
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
            $projectId = $this->backup->server->project_id;
            $backupId = $this->backup->id;

            foreach ($this->backup->files as $file) {
                $file->status = BackupFileStatus::DELETING;
                $file->save();
                $file->deleteFile();
            }

            if ($this->backup->files()->exists()) {
                $this->backup->status = BackupStatus::DELETE_FAILED;
                $this->backup->save();

                SocketEvent::dispatch(new SocketEventDTO(
                    projectId: $projectId,
                    type: 'backup.updated',
                    data: new BackupResource($this->backup),
                ));

                return;
            }

            $this->backup->delete();

            SocketEvent::dispatch(new SocketEventDTO(
                projectId: $projectId,
                type: 'backup.deleted',
                data: ['id' => $backupId],
            ));
        });
    }

    public function failed(Exception $e): void
    {
        ServerLog::log($this->backup->server, 'delete-backup-failed', $e->getMessage());

        if ($this->backup->exists) {
            $this->backup->status = BackupStatus::DELETE_FAILED;
            $this->backup->save();
            $this->backup->files()
                ->where('status', BackupFileStatus::DELETING)
                ->update(['status' => BackupFileStatus::DELETE_FAILED]);

            SocketEvent::dispatch(new SocketEventDTO(
                projectId: $this->backup->server->project_id,
                type: 'backup.updated',
                data: new BackupResource($this->backup),
            ));
        }
    }
}
