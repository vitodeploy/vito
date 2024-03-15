<?php

namespace App\Actions\Database;

use App\Models\Database;
use App\Models\DatabaseUser;
use App\Models\Server;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LinkUser
{
    /**
     * @throws ValidationException
     */
    public function link(DatabaseUser $databaseUser, array $input): void
    {
        if (! isset($input['databases']) || ! is_array($input['databases'])) {
            $input['databases'] = [];
        }

        $this->validate($databaseUser->server, $input);

        $dbs = Database::query()
            ->where('server_id', $databaseUser->server_id)
            ->whereIn('name', $input['databases'])
            ->count();
        if (count($input['databases']) !== $dbs) {
            throw ValidationException::withMessages(['databases' => __('Databases not found!')]);
        }

        $databaseUser->databases = $input['databases'];

        // Unlink the user from all databases
        $databaseUser->server->database()->handler()->unlink(
            $databaseUser->username,
            $databaseUser->host
        );

        // Link the user to the selected databases
        $databaseUser->server->database()->handler()->link(
            $databaseUser->username,
            $databaseUser->host,
            $databaseUser->databases
        );

        $databaseUser->save();
    }

    private function validate(Server $server, array $input): void
    {
        $rules = [
            'databases.*' => [
                'required',
                Rule::exists('databases', 'name')->where('server_id', $server->id),
            ],
        ];

        Validator::make($input, $rules)->validate();
    }
}
