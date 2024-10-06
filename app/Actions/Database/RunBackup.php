<?php

namespace App\Actions\Database;

use App\Enums\BackupFileStatus;
use App\Enums\BackupStatus;
use App\Models\Backup;
use App\Models\BackupFile;
use App\SSH\Services\Database\Database;
use Illuminate\Support\Str;

class RunBackup
{
    public function run(Backup $backup): BackupFile
    {
        $file = new BackupFile([
            'backup_id' => $backup->id,
            'name' => Str::of($backup->database->name)->slug().'-'.now()->format('YmdHis'),
            'status' => BackupFileStatus::CREATING,
        ]);
        $file->save();

        dispatch(function () use ($file, $backup) {
            /** @var Database $databaseHandler */
            $databaseHandler = $file->backup->server->database()->handler();
            $databaseHandler->runBackup($file);
            $file->status = BackupFileStatus::CREATED;
            $file->save();

            if ($backup->status !== BackupStatus::RUNNING) {
                $backup->status = BackupStatus::RUNNING;
                $backup->save();
            }
        })->catch(function () use ($file, $backup) {
            $backup->status = BackupStatus::FAILED;
            $backup->save();
            $file->status = BackupFileStatus::FAILED;
            $file->save();
        })->onConnection('ssh');

        return $file;
    }
}
