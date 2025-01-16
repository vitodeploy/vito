<?php

namespace App\Actions\Database;

use App\Models\BackupFile;
use App\Models\Server;
use Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ManageBackupFile
{
    public function download(Server $server, BackupFile $file): StreamedResponse
    {
        $localFilename = "backup_{$file->id}_{$file->name}.zip";

        if (! Storage::disk('backups')->exists($localFilename)) {
            $provider = $file->backup->storage;
            $databaseName = $file->backup->database->name;
            $storagePath = rtrim($provider->credentials['path'], '/');
            $remotePath = $storagePath.'/'.$databaseName.'/'.$file->name.'.zip';

            $sftp = $server->sftp();
            $sftp->download(
                Storage::disk('backups')->path($localFilename),
                $remotePath
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
}
