<?php

namespace App\Actions\Database;

use App\Enums\BackupStatus;
use App\Enums\DatabaseStatus;
use App\Models\Backup;
use App\Models\Server;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateBackup
{
    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function create($type, Server $server, array $input): Backup
    {
        $this->validate($type, $server, $input);

        $backup = new Backup([
            'type' => $type,
            'server_id' => $server->id,
            'database_id' => $input['backup_database'] ?? null,
            'storage_id' => $input['backup_storage'],
            'interval' => $input['backup_interval'] == 'custom' ? $input['backup_custom'] : $input['backup_interval'],
            'keep_backups' => $input['backup_keep'],
            'status' => BackupStatus::RUNNING,
        ]);
        $backup->save();

        app(RunBackup::class)->run($backup);

        return $backup;
    }

    /**
     * @throws ValidationException
     */
    private function validate($type, Server $server, array $input): void
    {
        $rules = [
            'backup_storage' => [
                'required',
                Rule::exists('storage_providers', 'id'),
            ],
            'backup_keep' => [
                'required',
                'numeric',
                'min:1',
            ],
            'backup_interval' => [
                'required',
                Rule::in([
                    '0 * * * *',
                    '0 0 * * *',
                    '0 0 * * 0',
                    '0 0 1 * *',
                    'custom',
                ]),
            ],
        ];
        if ($input['backup_interval'] == 'custom') {
            $rules['backup_custom'] = [
                'required',
            ];
        }
        if ($type === 'database') {
            $rules['backup_database'] = [
                'required',
                Rule::exists('databases', 'id')
                    ->where('server_id', $server->id)
                    ->where('status', DatabaseStatus::READY),
            ];
        }
        Validator::make($input, $rules)->validate();
    }
}
