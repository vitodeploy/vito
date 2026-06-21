<?php

namespace App\Jobs\Backup;

use App\Actions\Backup\BroadcastBackupUpdate;
use App\DTOs\SocketEventDTO;
use App\Enums\BackupFileStatus;
use App\Events\SocketEvent;
use App\Models\Backup;
use App\Models\ServerLog;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

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
                $this->backup->status = null;
                $this->backup->save();

                app(BroadcastBackupUpdate::class)->broadcast($this->backup);

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
            DB::transaction(function (): void {
                $this->backup->status = null;
                $this->backup->save();
                $this->backup->files()
                    ->where('status', BackupFileStatus::DELETING)
                    ->update(['status' => BackupFileStatus::DELETE_FAILED]);
            });

            app(BroadcastBackupUpdate::class)->broadcast($this->backup);
        }
    }
}
