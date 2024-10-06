<?php

namespace App\Actions\Database;

use App\Enums\DatabaseStatus;
use App\Models\Database;
use App\Models\Server;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateDatabase
{
    public function create(Server $server, array $input): Database
    {
        $database = new Database([
            'server_id' => $server->id,
            'name' => $input['name'],
        ]);
        /** @var \App\SSH\Services\Database\Database $databaseHandler */
        $databaseHandler = $server->database()->handler();
        $databaseHandler->create($database->name);
        $database->status = DatabaseStatus::READY;
        $database->save();

        if (isset($input['user']) && $input['user']) {
            $databaseUser = app(CreateDatabaseUser::class)->create($server, $input, [$database->name]);

            app(LinkUser::class)->link($databaseUser, ['databases' => [$database->name]]);
        }

        return $database;
    }

    /**
     * @throws ValidationException
     */
    public static function rules(Server $server, array $input): array
    {
        $rules = [
            'name' => [
                'required',
                'alpha_dash',
                Rule::unique('databases', 'name')->where('server_id', $server->id),
            ],
        ];
        if (isset($input['user']) && $input['user']) {
            $rules['username'] = [
                'required',
                'alpha_dash',
                Rule::unique('database_users', 'username')->where('server_id', $server->id),
            ];
            $rules['password'] = [
                'required',
                'min:6',
            ];
        }
        if (isset($input['remote']) && $input['remote']) {
            $rules['host'] = 'required';
        }

        return $rules;
    }
}
