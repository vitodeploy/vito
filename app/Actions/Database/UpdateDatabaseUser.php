<?php

namespace App\Actions\Database;

use App\Models\DatabaseUser;
use App\Models\Service;
use App\Services\Database\Database;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpdateDatabaseUser
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function update(DatabaseUser $databaseUser, array $input): DatabaseUser
    {
        $this->validate($databaseUser, $input);

        $oldHost = $databaseUser->host;
        $newPassword = $input['password'] ?? null;
        $newHost = null;

        if (isset($input['remote'])) {
            $newHost = $input['remote'] ? ($input['host'] ?? '%') : 'localhost';
            if ($newHost !== $oldHost) {
                $databaseUser->host = $newHost;
            } else {
                $newHost = null;
            }
        }

        if ($newPassword) {
            $databaseUser->password = $newPassword;
        }

        if ($newPassword || $newHost) {
            /** @var Service $service */
            $service = $databaseUser->server->database();

            /** @var Database $databaseHandler */
            $databaseHandler = $service->handler();
            $databaseHandler->updateUser(
                $databaseUser->username,
                $oldHost,
                $newPassword,
                $newHost
            );
        }

        $databaseUser->save();

        if ($newHost) {
            $this->updatePermissions($databaseUser, $oldHost, $newHost);
        }

        return $databaseUser;
    }

    private function validate(DatabaseUser $databaseUser, array $input): void
    {
        $rules = [];

        if (isset($input['password'])) {
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

    private function updatePermissions(DatabaseUser $databaseUser, string $oldHost, string $newHost): void
    {
        if (count($databaseUser->databases) > 0) {
            /** @var Service $service */
            $service = $databaseUser->server->database();

            /** @var Database $databaseHandler */
            $databaseHandler = $service->handler();

            $databaseHandler->unlink($databaseUser->username, $oldHost);

            $databaseHandler->link($databaseUser->username, $newHost, $databaseUser->databases);
        }
    }
}
