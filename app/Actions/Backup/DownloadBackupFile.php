<?php

namespace App\Actions\Backup;

use App\Enums\BackupFileStatus;
use App\Enums\BackupType;
use App\Models\BackupFile;
use App\Models\StorageProvider;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadBackupFile
{
    public function run(BackupFile $backupFile): StreamedResponse
    {
        Validator::make(['status' => $backupFile->status->value], [
            'status' => [Rule::in([
                BackupFileStatus::CREATED->value,
                BackupFileStatus::RESTORED->value,
                BackupFileStatus::RESTORE_FAILED->value,
            ])],
        ], ['status.in' => 'This backup file is not available for download.'])->validate();

        /** @var ?StorageProvider $storage */
        $storage = $backupFile->backup->storage;
        abort_unless($storage && $storage->hasProviderHandler(), 404, 'The storage provider is unavailable.');
        abort_if($backupFile->backup->type === BackupType::DATABASE && ! $backupFile->backup->database, 404);

        $download = $storage->provider()->download($backupFile);

        return response()->streamDownload(
            static function () use ($download): void {
                set_time_limit(0);
                ($download['stream'])();
            },
            basename($backupFile->path()),
            [
                'Content-Type' => 'application/octet-stream',
                'Content-Length' => (string) $download['size'],
                'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
                'X-Content-Type-Options' => 'nosniff',
                'X-Accel-Buffering' => 'no',
            ],
        );
    }
}
