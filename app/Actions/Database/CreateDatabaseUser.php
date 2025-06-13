<?php

namespace App\Actions\Database;

use App\Enums\DatabaseUserStatus;
use App\Models\DatabaseUser;
use App\Models\Server;
use App\Models\Service;
use App\Services\Database\Database;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateDatabaseUser
{
    /**
     * @param  array<string, mixed>  $input
     * @param  array<string>  $links
     *
     * @throws ValidationException
     */
    public function create(Server $server, array $input, array $links = []): DatabaseUser
    {
        Validator::make($input, self::rules($server, $input))->validate();

        $databaseUser = new DatabaseUser([
            'server_id' => $server->id,
            'username' => $input['username'],
            'password' => $input['password'],
            'host' => (isset($input['remote']) && $input['remote']) || isset($input['host']) ? $input['host'] : 'localhost',
            'databases' => $links,
        ]);

        /** @var Service $service */
        $service = $server->database();

        /** @var Database $databaseHandler */
        $databaseHandler = $service->handler();
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
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     *
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
