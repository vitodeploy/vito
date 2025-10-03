<?php

namespace App\Actions\Backup;

use App\Enums\BackupFileStatus;
use App\Enums\BackupStatus;
use App\Enums\BackupType;
use App\Models\Backup;
use App\Models\BackupFile;
use App\Models\Service;
use App\Services\Database\Database;
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

        dispatch(function () use ($file, $backup): void {
            if ($backup->type === BackupType::DATABASE) {
                /** @var Service $service */
                $service = $backup->server->database();
                /** @var Database $databaseHandler */
                $databaseHandler = $service->handler();
                $databaseHandler->runBackup($file);
            }

            if ($backup->type === BackupType::FILE) {
                $this->compressAndUploadFile($file, $backup);
            }

            $file->status = BackupFileStatus::CREATED;
            $file->save();

            if ($backup->status !== BackupStatus::RUNNING) {
                $backup->status = BackupStatus::RUNNING;
                $backup->save();
            }
        })->catch(function () use ($file, $backup): void {
            $backup->status = BackupStatus::FAILED;
            $backup->save();
            $file->status = BackupFileStatus::FAILED;
            $file->save();
        })->onQueue('ssh');

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

        // Upload to storage provider
        $upload = $backup->storage->provider()->ssh($server)->upload(
            $tempZipPath,
            $file->path()
        );

        // Clean up temporary file
        $server->os()->deleteFile($tempZipPath);

        // Set file size from upload response
        $file->size = $upload['size'];
        $file->save();
    }
}
