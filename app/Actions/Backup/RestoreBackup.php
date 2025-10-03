<?php

namespace App\Actions\Backup;

use App\Enums\BackupFileStatus;
use App\Enums\BackupType;
use App\Models\BackupFile;
use App\Models\Database;
use App\Models\Server;
use App\Models\Service;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RestoreBackup
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function restore(BackupFile $backupFile, array $input): void
    {
        $this->validate($backupFile->backup->server, $input, $backupFile->backup->type);

        $backup = $backupFile->backup;
        $backupFile->status = BackupFileStatus::RESTORING;

        if ($backup->type === BackupType::DATABASE) {
            $this->restoreDatabase($backupFile, $input);
        }

        if ($backup->type === BackupType::FILE) {
            $this->restoreFile($backupFile, $input);
        }
    }

    private function restoreDatabase(BackupFile $backupFile, array $input): void
    {
        /** @var Database $database */
        $database = Database::query()->findOrFail($input['database']);
        $backupFile->restored_to = $database->name;
        $backupFile->save();

        dispatch(function () use ($backupFile, $database): void {
            /** @var Service $service */
            $service = $database->server->database();
            /** @var \App\Services\Database\Database $databaseHandler */
            $databaseHandler = $service->handler();
            $databaseHandler->restoreBackup($backupFile, $database->name);
            $backupFile->status = BackupFileStatus::RESTORED;
            $backupFile->restored_at = now();
            $backupFile->save();
        })->catch(function () use ($backupFile): void {
            $backupFile->status = BackupFileStatus::RESTORE_FAILED;
            $backupFile->save();
        })->onQueue('ssh');
    }

    private function restoreFile(BackupFile $backupFile, array $input): void
    {
        // File backup restoration
        $restorePath = $input['path'];
        $owner = $input['owner'] ?? 'vito:vito';
        $permissions = $input['permissions'] ?? '755';

        $backupFile->restored_to = $restorePath;
        $backupFile->save();

        dispatch(function () use ($backupFile, $restorePath, $owner, $permissions): void {
            $server = $backupFile->backup->server;
            $tempBackupPath = $backupFile->tempPath();

            // Download backup from storage provider
            $backupFile->backup->storage->provider()->ssh($server)->download(
                $backupFile->path(),
                $tempBackupPath
            );

            // Extract the archive using OS service with custom owner and permissions
            $server->os()->extractArchive($tempBackupPath, $restorePath, $owner, $permissions);

            // Clean up temporary file
            $server->os()->deleteFile($tempBackupPath);

            $backupFile->status = BackupFileStatus::RESTORED;
            $backupFile->restored_at = now();
            $backupFile->save();
        })->catch(function () use ($backupFile): void {
            $backupFile->status = BackupFileStatus::RESTORE_FAILED;
            $backupFile->save();
            $backupFile->backup->server->os()->deleteFile($backupFile->tempPath());
        })->onQueue('ssh');
    }

    private function validate(Server $server, array $input, BackupType $backupType): void
    {
        $rules = [];

        if ($backupType === BackupType::DATABASE) {
            $rules['database'] = [
                'required',
                Rule::exists('databases', 'id')->where('server_id', $server->id),
            ];
        } else {
            $rules['path'] = [
                'required',
                'string',
                'min:1',
            ];
            $rules['owner'] = [
                'required',
                'string',
                'regex:/^[a-zA-Z0-9_-]+(:[a-zA-Z0-9_-]+)?$/',
            ];
            $rules['permissions'] = [
                'required',
                'string',
                'regex:/^[0-7]{3,4}$/',
            ];
        }

        Validator::make($input, $rules)->validate();
    }
}
