<?php

namespace App\Actions\Database;

use App\Enums\DatabaseStatus;
use App\Models\Database;
use App\Models\Server;
use App\Models\Service;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateDatabase
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function create(Server $server, array $input): Database
    {
        Validator::make($input, self::rules($server, $input))->validate();

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

        if (isset($input['user']) && $input['user']) {
            $databaseUser = app(CreateDatabaseUser::class)->create($server, $input, [$database->name]);

            app(LinkUser::class)->link($databaseUser, ['databases' => [$database->name]]);
        }

        return $database;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public static function rules(Server $server, array $input): array
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
