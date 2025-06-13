<?php

namespace App\Actions\Database;

use App\Enums\BackupFileStatus;
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
        Validator::make($input, self::rules($backupFile->backup->server))->validate();

        /** @var Database $database */
        $database = Database::query()->findOrFail($input['database']);
        $backupFile->status = BackupFileStatus::RESTORING;
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
        })->onConnection('ssh');
    }

    /**
     * @return array<string, array<string>>
     */
    public static function rules(Server $server): array
    {
        return [
            'database' => [
                'required',
                Rule::exists('databases', 'id')->where('server_id', $server->id),
            ],
        ];
    }
}
