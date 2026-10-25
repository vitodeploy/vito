<?php

namespace App\Actions\Backup;

use App\DTOs\SocketEventDTO;
use App\Enums\BackupFileStatus;
use App\Events\SocketEvent;
use App\Http\Resources\BackupFileResource;
use App\Jobs\Backup\DeleteFileJob;
use App\Models\BackupFile;
use App\Models\Server;
use Illuminate\Support\Facades\Log;

class ManageBackupFile
{
    public function delete(BackupFile $file): void
    {
        // Skip files that are already being deleted. Without this guard the
        // retention logic can dispatch duplicate DeleteFileJob instances for
        // the same file on every backup run, which floods the queue when the
        // server or storage provider is unreachable and deletions never settle.
        if ($file->status === BackupFileStatus::DELETING) {
            return;
        }

        $server = Server::find($file->backup->server_id);

        if ($server === null) {
            Log::warning('Deleting orphaned backup file without a server', [
                'backup_file_id' => $file->id,
                'backup_id' => $file->backup_id,
            ]);
            $file->delete();

            return;
        }

        $file->status = BackupFileStatus::DELETING;
        $file->message = null;
        $file->save();

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $server->project_id,
            type: 'backup-file.updated',
            data: new BackupFileResource($file),
        ));

        dispatch(new DeleteFileJob($file))->onQueue('ssh');
    }
}
