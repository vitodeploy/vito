<?php

namespace App\Actions\Site;

use App\Helpers\Apr1Hasher;
use App\Models\Site;
use App\Services\Webserver\Nginx;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpdateBasicAuth
{
    private const NGINX_AUTH_DIR = '/etc/nginx/auth';

    /**
     * @param  array<string, mixed>  $input
     */
    public function update(Site $site, array $input): void
    {
        $input = $this->normaliseInput($input);
        $this->validate($site, $input);

        $existing = collect($site->type_data['basic_auth']['users'] ?? [])->keyBy('username');

        $users = collect($input['users'] ?? [])
            ->map(function (array $u) use ($existing): ?array {
                $username = trim((string) ($u['username'] ?? ''));
                $plain = (string) ($u['password'] ?? '');

                if ($plain !== '') {
                    return [
                        'username' => $username,
                        'apr1' => Apr1Hasher::hash($plain),
                        'bcrypt' => password_hash($plain, PASSWORD_BCRYPT),
                    ];
                }

                $prev = $existing->get($username);
                if (! $prev) {
                    return null;
                }

                return [
                    'username' => $username,
                    'apr1' => $prev['apr1'] ?? null,
                    'bcrypt' => $prev['bcrypt'] ?? null,
                ];
            })
            ->filter(fn (?array $u) => $u !== null && ! empty($u['apr1']) && ! empty($u['bcrypt']))
            ->values()
            ->all();

        $enabled = (bool) ($input['enabled'] ?? false) && count($users) > 0;

        DB::transaction(function () use ($site, $enabled, $users): void {
            $typeData = $site->type_data ?? [];
            $typeData['basic_auth'] = [
                'enabled' => $enabled,
                'users' => $users,
            ];
            $site->update(['type_data' => $typeData]);
        });

        $this->writeAuthFile($site, $enabled ? $users : []);
        $site->webserver()->updateVHost($site);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function normaliseInput(array $input): array
    {
        $input['enabled'] = filter_var($input['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $input['users'] = is_array($input['users'] ?? null) ? array_values($input['users']) : [];

        return $input;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function validate(Site $site, array $input): void
    {
        Validator::make($input, [
            'enabled' => ['nullable', 'boolean'],
            'users' => ['array'],
            'users.*.username' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9._-]+$/', 'distinct'],
            'users.*.password' => ['nullable', 'string', 'max:255'],
        ])->validate();

        $existing = collect($site->type_data['basic_auth']['users'] ?? [])->keyBy('username');
        $errors = [];
        foreach ($input['users'] as $index => $u) {
            $username = trim((string) ($u['username'] ?? ''));
            $plain = (string) ($u['password'] ?? '');
            $hasExisting = $existing->has($username) && ! empty($existing[$username]['apr1']);
            if ($plain === '' && ! $hasExisting) {
                $errors["users.$index.password"] = 'Password is required for new users.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<int, array<string, string>>  $users
     */
    private function writeAuthFile(Site $site, array $users): void
    {
        if ($site->webserver()::id() !== Nginx::id()) {
            return;
        }

        $path = $site->htpasswdPath();

        if (empty($users)) {
            $site->server->ssh()->exec(
                view('ssh.services.webserver.nginx.remove-basic-auth-file', ['path' => $path]),
                'remove-basic-auth-file',
                $site->id,
            );

            return;
        }

        $lines = array_map(fn (array $u) => $u['username'].':'.$u['apr1']."\n", $users);
        $usernames = implode(', ', array_map(fn (array $u) => $u['username'], $users));

        $site->server->ssh()->exec(
            view('ssh.services.webserver.nginx.write-basic-auth-file', [
                'dir' => self::NGINX_AUTH_DIR,
                'path' => $path,
                'lines' => $lines,
                'userCount' => count($users),
                'usernames' => $usernames,
                'nginxUser' => $site->server->getSshUser(),
            ]),
            'write-basic-auth-file',
            $site->id,
        );
    }
}
