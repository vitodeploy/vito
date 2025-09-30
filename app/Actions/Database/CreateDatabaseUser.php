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
        $this->validate($server, $input);

        $databaseUser = new DatabaseUser([
            'server_id' => $server->id,
            'username' => $input['username'],
            'password' => $input['password'],
            'host' => (isset($input['remote']) && $input['remote']) || isset($input['host']) ? $input['host'] : 'localhost',
            'databases' => $links,
            'permission' => $input['permission'] ?? 'admin',
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
            app(LinkUser::class)->link($databaseUser, [
                'databases' => $links,
            ]);
        }

        return $databaseUser;
    }

    private function validate(Server $server, array $input): void
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
            'permission' => [
                'nullable',
                Rule::in(['read', 'write', 'admin']),
            ],
        ];
        if (isset($input['remote']) && $input['remote']) {
            $rules['host'] = 'required';
        }

        Validator::make($input, $rules)->validate();
    }
}
