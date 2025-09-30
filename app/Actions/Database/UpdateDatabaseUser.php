<?php

namespace App\Actions\Database;

use App\Enums\DatabaseUserPermission;
use App\Models\DatabaseUser;
use App\Models\Service;
use App\Services\Database\Database;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
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
        $oldPermission = $databaseUser->permission;
        $newPassword = $input['password'] ?? null;
        $newHost = null;
        $permissionChanged = false;

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

        if ($input['permission'] !== $oldPermission->value) {
            $databaseUser->permission = $input['permission'];
            $permissionChanged = true;
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

        if ($newHost || $permissionChanged) {
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

        $rules['permission'] = [
            'required',
            Rule::in(array_map(fn ($case) => $case->value, DatabaseUserPermission::cases())),
        ];

        Validator::make($input, $rules)->validate();
    }

    private function updatePermissions(DatabaseUser $databaseUser, string $oldHost, ?string $newHost): void
    {
        if (count($databaseUser->databases) > 0) {
            /** @var Service $service */
            $service = $databaseUser->server->database();

            /** @var Database $databaseHandler */
            $databaseHandler = $service->handler();

            $databaseHandler->unlink($databaseUser->username, $oldHost);

            $databaseHandler->link(
                $databaseUser->username,
                $newHost ?? $databaseUser->host,
                $databaseUser->databases,
                $databaseUser->permission->value
            );
        }
    }
}
