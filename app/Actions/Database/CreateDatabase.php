<?php

namespace App\Actions\Database;

use App\Enums\DatabaseStatus;
use App\Enums\DatabaseUserPermission;
use App\Models\Database;
use App\Models\Server;
use App\Models\Service;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateDatabase
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function create(Server $server, array $input): Database
    {
        $this->validate($server, $input);

        $database = new Database([
            'server_id' => $server->id,
            'charset' => $input['charset'],
            'collation' => $input['collation'],
            'name' => $input['name'],
        ]);

        /** @var Service $service */
        $service = $server->database();

        /** @var \App\Services\Database\Database $databaseHandler */
        $databaseHandler = $service->handler();
        $databaseHandler->create($database->name, $database->charset, $database->collation);
        $database->status = DatabaseStatus::READY;
        $database->save();

        $hasCreatedUser = false;
        if (isset($input['user']) && $input['user'] && isset($input['existing_user_id']) && $input['existing_user_id']) {
            // Link existing database user
            $databaseUser = $server->databaseUsers()->findOrFail($input['existing_user_id']);
            $databases = $databaseUser->databases ?? [];
            $databases[] = $database->name;
            app(LinkUser::class)->link($databaseUser, ['databases' => $databases]);
            $hasCreatedUser = true;
        }

        if (! $hasCreatedUser && (isset($input['username']) && $input['username'])) {
            app(CreateDatabaseUser::class)->create($server, [
                'username' => $input['username'],
                'password' => $input['password'],
                'permission' => DatabaseUserPermission::ADMIN->value,
            ], [$database->name]);
        }

        return $database;
    }

    private function validate(Server $server, array $input): void
    {
        $rules = [
            'name' => [
                'required',
                'alpha_dash',
                Rule::unique('databases', 'name')->where('server_id', $server->id)->whereNull('deleted_at'),
            ],
            'charset' => [
                'required',
                'string',
            ],
            'collation' => [
                'required',
                'string',
            ],
            'username' => [
                'nullable',
                'alpha_dash',
                Rule::unique('database_users', 'username')->where('server_id', $server->id)->whereNull('deleted_at'),
            ],
            'password' => [
                'nullable',
                'string',
            ],
        ];
        if (isset($input['user']) && $input['user']) {
            $rules['existing_user_id'] = [
                'required',
                'integer',
                Rule::exists('database_users', 'id')->where('server_id', $server->id),
            ];
        }

        Validator::make($input, $rules)->validate();
    }
}
