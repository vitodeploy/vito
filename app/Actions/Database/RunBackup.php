<?php

namespace App\Actions\Database;

use App\Enums\BackupFileStatus;
use App\Enums\BackupStatus;
use App\Models\Backup;
use App\Models\BackupFile;
use App\Models\Service;
use App\Services\Database\Database;
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

        dispatch(function () use ($file, $backup): void {
            /** @var Service $service */
            $service = $backup->server->database();
            /** @var Database $databaseHandler */
            $databaseHandler = $service->handler();
            $databaseHandler->runBackup($file);
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
}
