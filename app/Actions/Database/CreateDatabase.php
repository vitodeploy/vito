<?php

namespace App\Actions\Database;

use App\Enums\DatabaseStatus;
use App\Models\Database;
use App\Models\Server;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateDatabase
{
    /**
     * @throws ValidationException
     */
    public function create(Server $server, array $input): Database
    {
        $this->validate($server, $input);

        $database = new Database([
            'server_id' => $server->id,
            'name' => $input['name'],
        ]);
        /** @var \App\SSH\Services\Database\Database */
        $databaseHandler = $server->database()->handler();
        $databaseHandler->create($database->name);
        $database->status = DatabaseStatus::READY;
        $database->save();

        return $database;
    }

    /**
     * @throws ValidationException
     */
    private function validate(Server $server, array $input): void
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
        Validator::make($input, $rules)->validate();
    }
}
