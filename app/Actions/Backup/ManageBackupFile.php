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
