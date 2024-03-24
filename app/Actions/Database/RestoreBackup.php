<?php

namespace App\Actions\Database;

use App\Enums\BackupFileStatus;
use App\Models\BackupFile;
use App\Models\Database;
use Illuminate\Support\Facades\Validator;

class RestoreBackup
{
    public function restore(BackupFile $backupFile, array $input): void
    {
        $this->validate($input);

        /** @var Database $database */
        $database = Database::query()->findOrFail($input['database']);
        $backupFile->status = BackupFileStatus::RESTORING;
        $backupFile->restored_to = $database->name;
        $backupFile->save();

        dispatch(function () use ($backupFile, $database) {
            $database->server->database()->handler()->restoreBackup($backupFile, $database->name);
            $backupFile->status = BackupFileStatus::RESTORED;
            $backupFile->restored_at = now();
            $backupFile->save();
        })->catch(function () use ($backupFile) {
            $backupFile->status = BackupFileStatus::RESTORE_FAILED;
            $backupFile->save();
        })->onConnection('ssh');
    }

    private function validate(array $input): void
    {
        Validator::make($input, [
            'database' => 'required|exists:databases,id',
        ])->validate();
    }
}
