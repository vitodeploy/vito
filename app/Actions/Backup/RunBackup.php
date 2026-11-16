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
        $backupName = $backup->type === BackupType::FILE
            ? basename($backup->path)
            : $backup->database?->name;

        $name = Str::of($backupName)->slug().'-'.now()->format('YmdHis');
        if ($backup->prefix !== null && $backup->prefix !== '') {
            $name = $backup->prefix.'-'.$name;
        }

        $file = new BackupFile([
            'backup_id' => $backup->id,
            'name' => $name,
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

        $server->os()->deleteFile($tempZipPath);

        $server->os()->compress($sourcePath, $tempZipPath);

        $size = trim($server->ssh()->exec(
            'stat -c%s '.escapeshellarg($tempZipPath).' || true',
            'backup-size'
        ));

        $upload = $backup->storage->provider()->ssh($server)->upload(
            $tempZipPath,
            $file->path()
        );

        $server->os()->deleteFile($tempZipPath);

        $file->size = is_numeric($size) ? (int) $size : ($upload['size'] ?? null);
        $file->save();
    }
}
