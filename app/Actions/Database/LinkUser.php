<?php

namespace App\Actions\Database;

use App\Models\Database;
use App\Models\DatabaseUser;
use App\Models\Server;
use App\Models\Service;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LinkUser
{
    /**
     * @param  array<string, mixed>  $input
     * @return DatabaseUser $databaseUser
     *
     * @throws ValidationException
     */
    public function link(DatabaseUser $databaseUser, array $input): DatabaseUser
    {
        Validator::make($input, self::rules($databaseUser->server))->validate();

        if (! isset($input['databases']) || ! is_array($input['databases'])) {
            $input['databases'] = [];
        }

        $dbs = Database::query()
            ->where('server_id', $databaseUser->server_id)
            ->whereIn('name', $input['databases'])
            ->count();
        if (count($input['databases']) !== $dbs) {
            throw ValidationException::withMessages(['databases' => __('Databases not found!')]);
        }

        $databaseUser->databases = $input['databases'];

        /** @var Service $service */
        $service = $databaseUser->server->database();

        /** @var \App\Services\Database\Database $handler */
        $handler = $service->handler();

        // Unlink the user from all databases
        $handler->unlink(
            $databaseUser->username,
            $databaseUser->host
        );

        // Link the user to the selected databases
        $handler->link(
            $databaseUser->username,
            $databaseUser->host,
            $databaseUser->databases
        );

        $databaseUser->save();

        $databaseUser->refresh();

        return $databaseUser;
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(Server $server): array
    {
        return [
            'databases.*' => [
                'nullable',
                Rule::exists('databases', 'name')->where('server_id', $server->id),
            ],
        ];
    }
}
