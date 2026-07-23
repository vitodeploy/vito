<?php

namespace App\Actions\Backup;

use App\DTOs\SocketEventDTO;
use App\Enums\BackupFileStatus;
use App\Events\SocketEvent;
use App\Http\Resources\BackupFileResource;
use App\Jobs\Backup\DeleteFileJob;
use App\Models\BackupFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ManageBackupFile
{
    /**
     * @throws Throwable
     */
    public function download(BackupFile $file): StreamedResponse
    {
        $server = $file->backup?->server;
        if ($server === null) {
            throw new RuntimeException('The backup server no longer exists.');
        }

        $server->ssh()->download(
            Storage::disk('tmp')->path(basename($file->path())),
            $file->path()
        );

        return Storage::disk('tmp')->download(basename($file->path()));
    }

    public function delete(BackupFile $file): void
    {
        $projectId = $file->backup?->server?->project_id;

        if ($projectId === null) {
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
            projectId: $projectId,
            type: 'backup-file.updated',
            data: new BackupFileResource($file),
        ));

        dispatch(new DeleteFileJob($file))->onQueue('ssh');
    }
}
