<?php

namespace App\Actions\Database;

use App\Enums\DatabaseUserStatus;
use App\Models\DatabaseUser;
use App\Models\EnvironmentAttachment;
use App\Models\Server;
use App\Models\Service;
use App\Services\Database\Database;
use Closure;
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
    public function create(Server $server, array $input, array $links = [], ?EnvironmentAttachment $attachment = null): DatabaseUser
    {
        /** @var Service $service */
        $service = $server->database();
        /** @var Database $databaseHandler */
        $databaseHandler = $service->handler();
        $databaseUser = $attachment?->databaseUsers()->where('username', $input['username'])
            ->when($databaseHandler->usesHost(), fn ($query) => $query->where('host', $this->resolveHost($input)))->first();
        if (! $databaseUser) {
            $this->validate($server, $input);
            $databaseUser = new DatabaseUser([
                'server_id' => $server->id,
                'username' => $input['username'],
                'password' => $input['password'],
                'host' => $this->resolveHost($input),
                'databases' => $links,
                'permission' => $input['permission'] ?? 'admin',
            ]);
            if ($attachment) {
                $databaseUser->environment_attachment_id = $attachment->id;
                $databaseUser->status = DatabaseUserStatus::CREATING;
                $databaseUser->save();
            }
        }

        $databaseUser->host = $this->resolveHost($input);
        if ($attachment) {
            $databaseUser->save();
        }
        if (! $attachment || $databaseUser->status !== DatabaseUserStatus::READY) {
            $databaseHandler->createUser(
                $databaseUser->username,
                $databaseUser->password,
                $databaseUser->host,
            );
        }
        $databaseUser->status = DatabaseUserStatus::READY;
        $databaseUser->save();

        if (count($links) > 0) {
            app(LinkUser::class)->link($databaseUser, [
                'databases' => $links,
            ], $attachment);
        }

        return $databaseUser;
    }

    private function validate(Server $server, array $input): void
    {
        /** @var Database $handler */
        $handler = $server->database()->handler();
        $host = $this->resolveHost($input);

        $rules = [
            'username' => [
                'required',
                'alpha_dash',
                function (string $attribute, mixed $value, Closure $fail) use ($handler, $server, $host): void {
                    if ($handler->databaseUserExists($server, (string) $value, $host)) {
                        $fail(__('A database user with this username and host already exists.'));
                    }
                },
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

        if ($handler->usesHost()) {
            $rules['host'] = [
                isset($input['remote']) && $input['remote'] ? 'required' : 'nullable',
                'regex:/^[A-Za-z0-9%._:\-]*$/',
            ];
        }

        Validator::make($input, $rules)->validate();
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function resolveHost(array $input): string
    {
        if (! empty($input['host'])) {
            return $input['host'];
        }

        return ! empty($input['remote']) ? '%' : 'localhost';
    }
}
