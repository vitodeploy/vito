<?php

namespace App\Actions\Database;

use App\Enums\BackupFileStatus;
use App\Models\BackupFile;
use App\Models\Database;

class RestoreBackup
{
    public function restore(BackupFile $backupFile, array $input): void
    {
        /** @var Database $database */
        $database = Database::query()->findOrFail($input['database']);
        $backupFile->status = BackupFileStatus::RESTORING;
        $backupFile->restored_to = $database->name;
        $backupFile->save();

        dispatch(function () use ($backupFile, $database) {
            /** @var \App\SSH\Services\Database\Database $databaseHandler */
            $databaseHandler = $database->server->database()->handler();
            $databaseHandler->restoreBackup($backupFile, $database->name);
            $backupFile->status = BackupFileStatus::RESTORED;
            $backupFile->restored_at = now();
            $backupFile->save();
        })->catch(function () use ($backupFile) {
            $backupFile->status = BackupFileStatus::RESTORE_FAILED;
            $backupFile->save();
        })->onConnection('ssh');
    }

    public static function rules(): array
    {
        return [
            'database' => [
                'required',
                'exists:databases,id',
            ],
        ];
    }
}
