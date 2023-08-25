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
            'database_id' => $input['database'] ?? null,
            'storage_id' => $input['storage'],
            'interval' => $input['interval'] == 'custom' ? $input['custom'] : $input['interval'],
            'keep_backups' => $input['keep'],
            'status' => BackupStatus::RUNNING,
        ]);
        $backup->save();

        $backup->run();

        return $backup;
    }

    /**
     * @throws ValidationException
     */
    private function validate($type, Server $server, array $input): void
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
                Rule::in([
                    '0 * * * *',
                    '0 0 * * *',
                    '0 0 * * 0',
                    '0 0 1 * *',
                    'custom'
                ]),
            ],
        ];
        if ($input['interval'] == 'custom') {
            $rules['custom'] = [
                'required',
            ];
        }
        if ($type === 'database') {
            $rules['database'] = [
                'required',
                Rule::exists('databases', 'id')
                    ->where('server_id', $server->id)
                    ->where('status', DatabaseStatus::READY),
            ];
        }
        Validator::make($input, $rules)->validate();
    }
}
