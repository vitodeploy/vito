<?php

namespace App\Actions\Database;

use App\Enums\BackupFileStatus;
use App\Models\BackupFile;
use App\Models\Database;

class RestoreBackup
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function restore(BackupFile $backupFile, array $input): void
    {
        /** @var Database $database */
        $database = Database::query()->findOrFail($input['database']);
        $backupFile->status = BackupFileStatus::RESTORING;
        $backupFile->restored_to = $database->name;
        $backupFile->save();

        dispatch(function () use ($backupFile, $database): void {
            $service = $database->server->database();
            if (! $service) {
                throw new \Exception('Database service not found');
            }
            /** @var \App\SSH\Services\Database\Database $databaseHandler */
            $databaseHandler = $service->handler();
            $databaseHandler->restoreBackup($backupFile, $database->name);
            $backupFile->status = BackupFileStatus::RESTORED;
            $backupFile->restored_at = now();
            $backupFile->save();
        })->catch(function () use ($backupFile): void {
            $backupFile->status = BackupFileStatus::RESTORE_FAILED;
            $backupFile->save();
        })->onConnection('ssh');
    }

    /**
     * @return array<string, array<string>>
     */
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
