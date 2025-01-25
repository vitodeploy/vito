<?php

namespace App\Actions\Database;

use App\Enums\BackupFileStatus;
use App\Enums\BackupStatus;
use App\Enums\DatabaseStatus;
use App\Models\Backup;
use App\Models\Server;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ManageBackup
{
    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function create(Server $server, array $input): Backup
    {
        $backup = new Backup([
            'type' => 'database',
            'server_id' => $server->id,
            'database_id' => $input['database'] ?? null,
            'storage_id' => $input['storage'],
            'interval' => $input['interval'] == 'custom' ? $input['custom_interval'] : $input['interval'],
            'keep_backups' => $input['keep'],
            'status' => BackupStatus::RUNNING,
        ]);
        $backup->save();

        app(RunBackup::class)->run($backup);

        return $backup;
    }

    public function update(Backup $backup, array $input): void
    {
        $backup->interval = $input['interval'] == 'custom' ? $input['custom_interval'] : $input['interval'];
        $backup->keep_backups = $input['keep'];
        $backup->save();
    }

    public function delete(Backup $backup): void
    {
        $backup->status = BackupStatus::DELETING;
        $backup->save();

        dispatch(function () use ($backup) {
            $files = $backup->files;
            foreach ($files as $file) {
                $file->status = BackupFileStatus::DELETING;
                $file->save();

                $file->deleteFile();
            }

            $backup->delete();
        });
    }

    public static function rules(Server $server, array $input): array
    {
        $rules = [
            'storage' => [
                'required',
                Rule::exists('storage_providers', 'id'),
            ],
            'keep' => [
                'required',
                'numeric',
                'min:1',
            ],
            'interval' => [
                'required',
                Rule::in(array_keys(config('core.cronjob_intervals'))),
            ],
            'database' => [
                'required',
                Rule::exists('databases', 'id')
                    ->where('server_id', $server->id)
                    ->where('status', DatabaseStatus::READY),
            ],
        ];
        if ($input['interval'] == 'custom') {
            $rules['custom_interval'] = [
                'required',
            ];
        }

        return $rules;
    }
}
