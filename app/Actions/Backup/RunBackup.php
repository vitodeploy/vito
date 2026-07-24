<?php

namespace App\Actions\Backup;

use App\DTOs\SocketEventDTO;
use App\Enums\BackupFileStatus;
use App\Enums\BackupType;
use App\Events\SocketEvent;
use App\Http\Resources\BackupFileResource;
use App\Jobs\Backup\RunJob;
use App\Models\Backup;
use App\Models\BackupFile;
use Illuminate\Support\Str;

class RunBackup
{
    public function run(Backup $backup): BackupFile
    {
        // Determine the backup name based on type
        $backupName = $backup->type === BackupType::FILE
            ? basename($backup->path)
            : $backup->database?->name;

        $file = new BackupFile([
            'backup_id' => $backup->id,
            'name' => Str::of($backupName)->slug().'-'.now()->format('YmdHis'),
            'status' => BackupFileStatus::CREATING,
        ]);
        $file->save();
        $file->setRelation('backup', $backup);

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $backup->server->project_id,
            type: 'backup-file.created',
            data: new BackupFileResource($file),
        ));

        app(BroadcastBackupUpdate::class)->broadcast($backup);

        dispatch(new RunJob($file, $backup))->onQueue('ssh');

        return $file;
    }

    public function compressAndUploadFile(BackupFile $file, Backup $backup): void
    {
        $server = $backup->server;
        $sourcePath = $backup->path;
        $tempZipPath = $file->tempPath();

        // Remove any existing zip file first
        $server->os()->deleteFile($tempZipPath);

        // Compress the file/directory using OS service
        $server->os()->compress($sourcePath, $tempZipPath);

        $size = trim($server->ssh()->exec(
            'stat -c%s '.escapeshellarg($tempZipPath).' || true',
            'backup-size'
        ));

        // Upload to storage provider
        $upload = $backup->storage->provider()->ssh($server)->upload(
            $tempZipPath,
            $file->path()
        );

        // Clean up temporary file
        $server->os()->deleteFile($tempZipPath);

        $file->size = is_numeric($size) ? (int) $size : ($upload['size'] ?? null);
        $file->save();
    }
}
