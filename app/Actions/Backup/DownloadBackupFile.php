<?php

namespace App\Actions\Backup;

use App\Enums\BackupFileStatus;
use App\Models\BackupFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadBackupFile
{
    public function run(BackupFile $backupFile): StreamedResponse
    {
        abort_unless($backupFile->isLocal(), 404, 'Downloading from this storage provider is not supported.');

        abort_unless(in_array($backupFile->status, [
            BackupFileStatus::CREATED,
            BackupFileStatus::RESTORED,
            BackupFileStatus::RESTORE_FAILED,
        ], true), 409, 'This backup file is not available for download.');

        $path = $backupFile->path();
        $ssh = $backupFile->backup->server->ssh();

        abort_unless($ssh->isRegularFile($path), 404, 'The backup file does not exist on the server.');

        return response()->streamDownload(
            static function () use ($ssh, $path): void {
                $ssh->streamDownload($path, static function (string $chunk): void {
                    echo $chunk;
                    flush();
                });
            },
            basename($path),
            [
                'Content-Type' => 'application/octet-stream',
                'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
                'X-Content-Type-Options' => 'nosniff',
                'X-Accel-Buffering' => 'no',
            ],
        );
    }
}
