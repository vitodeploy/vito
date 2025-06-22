<?php

namespace App\Actions\Database;

use App\Enums\BackupFileStatus;
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
        $file->save();

        dispatch(function () use ($file): void {
            $file->deleteFile();
        })->onQueue('ssh');
    }
}
