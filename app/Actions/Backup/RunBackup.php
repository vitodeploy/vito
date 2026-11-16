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
    private const int MAX_FILE_NAME_BYTES = 255;

    public function run(Backup $backup): BackupFile
    {
        $backupName = $backup->type === BackupType::FILE
            ? basename($backup->path)
            : $backup->database?->name;

        $file = new BackupFile([
            'backup_id' => $backup->id,
            'name' => $this->fileName($backup, (string) $backupName),
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

    /**
     * Builds "[prefix-]source-YmdHis", shortening the source part so the
     * file name plus its extension stays within the 255-byte limit of
     * common filesystems.
     */
    private function fileName(Backup $backup, string $backupName): string
    {
        $prefix = $backup->prefix !== null && $backup->prefix !== '' ? $backup->prefix.'-' : '';
        $suffix = '-'.now()->format('YmdHis');
        $available = self::MAX_FILE_NAME_BYTES
            - strlen(BackupFile::extensionFor($backup->type))
            - strlen($prefix)
            - strlen($suffix);

        $source = rtrim(substr((string) Str::slug($backupName), 0, max($available, 0)), '-');

        return $prefix.$source.$suffix;
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
