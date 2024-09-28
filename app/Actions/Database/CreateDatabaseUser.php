<?php

namespace App\Actions\Database;

use App\Enums\DatabaseUserStatus;
use App\Models\DatabaseUser;
use App\Models\Server;
use App\SSH\Services\Database\Database;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateDatabaseUser
{
    /**
     * @throws ValidationException
     */
    public function create(Server $server, array $input, array $links = []): DatabaseUser
    {
        $databaseUser = new DatabaseUser([
            'server_id' => $server->id,
            'username' => $input['username'],
            'password' => $input['password'],
            'host' => isset($input['remote']) && $input['remote'] ? $input['host'] : 'localhost',
            'databases' => $links,
        ]);
        /** @var Database $databaseHandler */
        $databaseHandler = $server->database()->handler();
        $databaseHandler->createUser(
            $databaseUser->username,
            $databaseUser->password,
            $databaseUser->host
        );
        $databaseUser->status = DatabaseUserStatus::READY;
        $databaseUser->save();

        if (count($links) > 0) {
            app(LinkUser::class)->link($databaseUser, ['databases' => $links]);
        }

        return $databaseUser;
    }

    /**
     * @throws ValidationException
     */
    public static function rules(Server $server, array $input): array
    {
        $rules = [
            'username' => [
                'required',
                'alpha_dash',
                Rule::unique('database_users', 'username')->where('server_id', $server->id),
            ],
            'password' => [
                'required',
                'min:6',
            ],
        ];
        if (isset($input['remote']) && $input['remote']) {
            $rules['host'] = 'required';
        }

        return $rules;
    }
}
