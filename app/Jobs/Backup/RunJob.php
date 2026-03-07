<?php

namespace App\Jobs\Backup;

use App\Actions\Backup\RunBackup;
use App\DTOs\SocketEventDTO;
use App\Enums\BackupFileStatus;
use App\Enums\BackupStatus;
use App\Enums\BackupType;
use App\Events\SocketEvent;
use App\Http\Resources\BackupFileResource;
use App\Http\Resources\BackupResource;
use App\Models\Backup;
use App\Models\BackupFile;
use App\Models\ServerLog;
use App\Models\Service;
use App\Services\Database\Database;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(
        protected BackupFile $file,
        protected Backup $backup,
    ) {}

    public function handle(): void
    {
        $this->run("backup-{$this->backup->id}", function () {
            if ($this->backup->type === BackupType::DATABASE) {
                /** @var Service $service */
                $service = $this->backup->server->database();
                /** @var Database $databaseHandler */
                $databaseHandler = $service->handler();
                $databaseHandler->runBackup($this->file);
            }

            if ($this->backup->type === BackupType::FILE) {
                app(RunBackup::class)->compressAndUploadFile($this->file, $this->backup);
            }

            $this->file->status = BackupFileStatus::CREATED;
            $this->file->save();
            $this->broadcastFileUpdate();

            if ($this->backup->status !== BackupStatus::RUNNING) {
                $this->backup->status = BackupStatus::RUNNING;
                $this->backup->save();
            }
            $this->broadcastBackupUpdate();
        });
    }

    public function failed(Exception $e): void
    {
        $this->backup->status = BackupStatus::FAILED;
        $this->backup->save();
        $this->file->status = BackupFileStatus::FAILED;
        $this->file->save();
        $this->broadcastBackupUpdate();
        $this->broadcastFileUpdate();
        ServerLog::log($this->backup->server, 'run-backup-failed', $e->getMessage());
    }

    private function broadcastBackupUpdate(): void
    {
        $this->backup->refresh();

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $this->backup->server->project_id,
            type: 'backup.updated',
            data: new BackupResource($this->backup),
        ));
    }

    private function broadcastFileUpdate(): void
    {
        $this->file->refresh();

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $this->file->backup->server->project_id,
            type: 'backup-file.updated',
            data: new BackupFileResource($this->file),
        ));
    }
}
