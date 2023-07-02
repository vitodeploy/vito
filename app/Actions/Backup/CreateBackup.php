<?php

namespace App\Actions\Backup;

use App\Enums\DatabaseStatus;
use App\Models\Backup;
use App\Models\Database;
use App\Models\Server;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateBackup
{
    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function create($type, Server $server, User $user, array $input): Backup
    {
        $this->validate($type, $server, $user, $input);

        if ($type == 'database') {
            Gate::forUser($user)->authorize('viewAny', [Database::class, $server]);
        }

        $backup = new Backup([
            'name' => $input['name'],
            'type' => $type,
            'server_id' => $server->id,
            'database_id' => $input['database'] ?? null,
            'storage_id' => $input['storage'],
            'interval' => $input['interval'],
            'keep_backups' => $input['keep_backups'],
            'status' => 'running',
        ]);
        $backup->save();

        $backup->run();

        return $backup;
    }

    /**
     * @throws ValidationException
     */
    private function validate($type, Server $server, User $user, array $input): void
    {
        $rules = [
            'name' => 'required',
            'storage' => [
                'required',
                Rule::exists('storage_providers', 'id')
                    ->where('user_id', $user->id)
                    ->where('connected', 1),
            ],
            'keep_backups' => [
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
                ]),
            ],
        ];
        if ($type === 'database') {
            $rules['database'] = [
                'required',
                Rule::exists('databases', 'id')
                    ->where('server_id', $server->id)
                    ->where('status', DatabaseStatus::READY),
            ];
        }
        Validator::make($input, $rules)->validateWithBag('createBackup');
    }
}
