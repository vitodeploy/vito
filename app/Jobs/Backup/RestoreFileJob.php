<?php

namespace App\Jobs\Backup;

use App\DTOs\SocketEventDTO;
use App\Enums\BackupFileStatus;
use App\Events\SocketEvent;
use App\Facades\Notifier;
use App\Http\Resources\BackupFileResource;
use App\Models\BackupFile;
use App\Models\ServerLog;
use App\Notifications\RestoreFailed;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;
use Throwable;

class RestoreFileJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(
        protected BackupFile $backupFile,
        protected string $restorePath,
        protected string $owner,
        protected string $permissions,
    ) {}

    public function handle(): void
    {
        $server = $this->backupFile->backup->server;

        $this->run("backup-file-{$this->backupFile->id}", function () use ($server) {
            $tempBackupPath = $this->backupFile->tempPath();

            // Download backup from storage provider
            $this->backupFile->backup->storage->provider()->ssh($server)->download(
                $this->backupFile->path(),
                $tempBackupPath
            );

            // Extract the archive using OS service with custom owner and permissions
            $server->os()->extractArchive($tempBackupPath, $this->restorePath, $this->owner, $this->permissions);

            // Clean up temporary file
            $server->os()->deleteFile($tempBackupPath);

            $this->backupFile->status = BackupFileStatus::RESTORED;
            $this->backupFile->message = null;
            $this->backupFile->restored_at = now();
            $this->backupFile->save();
            $this->broadcastFileUpdate();
        });
    }

    public function failed(Exception $e): void
    {
        $server = $this->backupFile->backup->server;
        $this->backupFile->status = BackupFileStatus::RESTORE_FAILED;
        $this->backupFile->message = Str::limit($e->getMessage(), 1000);
        $this->backupFile->save();
        $this->broadcastFileUpdate();
        ServerLog::log($server, 'restore-file-failed', $e->getMessage());
        Notifier::send($server, new RestoreFailed($server, $this->backupFile));

        try {
            $server->os()->deleteFile($this->backupFile->tempPath());
        } catch (Throwable) {
        }
    }

    private function broadcastFileUpdate(): void
    {
        $this->backupFile->refresh();

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $this->backupFile->backup->server->project_id,
            type: 'backup-file.updated',
            data: new BackupFileResource($this->backupFile),
        ));
    }
}
