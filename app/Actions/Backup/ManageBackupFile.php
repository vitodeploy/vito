<?php

namespace App\Actions\Backup;

use App\DTOs\SocketEventDTO;
use App\Enums\BackupFileStatus;
use App\Events\SocketEvent;
use App\Http\Resources\BackupFileResource;
use App\Jobs\Backup\DeleteFileJob;
use App\Models\BackupFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ManageBackupFile
{
    /**
     * @throws Throwable
     */
    public function download(BackupFile $file): StreamedResponse
    {
        $file->backup->server->ssh()->download(
            Storage::disk('tmp')->path(basename($file->path())),
            $file->path()
        );

        return Storage::disk('tmp')->download(basename($file->path()));
    }

    public function delete(BackupFile $file): void
    {
        $file->status = BackupFileStatus::DELETING;
        $file->message = null;
        $file->save();

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $file->backup->server->project_id,
            type: 'backup-file.updated',
            data: new BackupFileResource($file),
        ));

        dispatch(new DeleteFileJob($file))->onQueue('ssh');
    }
}
