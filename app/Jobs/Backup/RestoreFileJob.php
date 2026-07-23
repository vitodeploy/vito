<?php

namespace App\Jobs\Backup;

use App\Actions\Backup\BroadcastBackupUpdate;
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

    public int $timeout;

    public function __construct(
        protected BackupFile $backupFile,
        protected string $restorePath,
        protected string $owner,
        protected string $permissions,
    ) {
        $this->timeout = max(300, (int) config('core.backup_run_timeout'));
    }

    protected function lockSeconds(): int
    {
        return $this->timeout + 60;
    }

    public function handle(): void
    {
        $server = $this->backupFile->backup->server;

        $this->run("backup-file-{$this->backupFile->id}", function () use ($server) {
            $tempBackupPath = $this->backupFile->tempPath();

            $this->backupFile->backup->storage->provider()->ssh($server)->download(
                $this->backupFile->path(),
                $tempBackupPath
            );

            $server->os()->extractArchive($tempBackupPath, $this->restorePath, $this->owner, $this->permissions);

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
        } catch (Throwable $cleanupError) {
            ServerLog::log($server, 'cleanup-failed-restore', $cleanupError->getMessage());
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

        app(BroadcastBackupUpdate::class)->broadcast($this->backupFile->backup);
    }
}
