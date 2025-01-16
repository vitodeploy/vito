<?php

namespace App\Actions\Database;

use App\Enums\BackupFileStatus;
use App\Models\BackupFile;
use Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ManageBackupFile
{
    public function download(BackupFile $file): StreamedResponse
    {
        $localFilename = "backup_{$file->id}_{$file->name}.zip";

        if (! Storage::disk('backups')->exists($localFilename)) {
            $file->backup->server->sftp()->download(
                Storage::disk('backups')->path($localFilename),
                $file->path()
            );
        }

        $stream = Storage::disk('backups')->readStream($localFilename);

        return response()->stream(
            function () use ($stream) {
                fpassthru($stream);
            },
            200,
            [
                'Content-Type' => Storage::disk('backups')->mimeType($localFilename),
                'Content-Length' => Storage::disk('backups')->size($localFilename),
                'Content-Disposition' => 'attachment; filename="'.$file->name.'.zip"',
            ]
        );
    }

    public function delete(BackupFile $file): void
    {
        $file->status = BackupFileStatus::DELETING;
        $file->save();

        dispatch(function () use ($file) {
            try {
                $storage = $file->backup->storage->provider();
                $storage->delete([$file->path()], $file->backup->server);
            }
            finally {
                $file->delete();
            }
        });
    }
}
