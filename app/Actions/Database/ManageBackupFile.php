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
        $localFilename = "backup_{$file->id}_{$file->name}.zip";

        if (! Storage::disk('backups')->exists($localFilename)) {
            $file->backup->server->ssh()->download(
                Storage::disk('backups')->path($localFilename),
                $file->path()
            );
        }

        return Storage::disk('backups')->download($localFilename, $file->name.'.zip');
    }

    public function delete(BackupFile $file): void
    {
        $file->status = BackupFileStatus::DELETING;
        $file->save();

        dispatch(function () use ($file) {
            $file->deleteFile();
        });
    }
}
