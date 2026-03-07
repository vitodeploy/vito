<?php

namespace App\Jobs\Backup;

use App\DTOs\SocketEventDTO;
use App\Enums\BackupFileStatus;
use App\Events\SocketEvent;
use App\Http\Resources\BackupFileResource;
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
            $this->broadcastFileUpdate();
        });
    }

    public function failed(Exception $e): void
    {
        $this->backupFile->status = BackupFileStatus::RESTORE_FAILED;
        $this->backupFile->save();
        $this->broadcastFileUpdate();
        ServerLog::log($this->database->server, 'restore-database-failed', $e->getMessage());
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
