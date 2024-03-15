<?php

namespace App\Actions\Database;

use App\Enums\BackupFileStatus;
use App\Models\Backup;
use App\Models\BackupFile;
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

        dispatch(function () use ($file) {
            $file->backup->server->database()->handler()->runBackup($file);
            $file->status = BackupFileStatus::CREATED;
            $file->save();
        })->catch(function () use ($file) {
            $file->status = BackupFileStatus::FAILED;
            $file->save();
        })->onConnection('ssh');

        return $file;
    }
}
