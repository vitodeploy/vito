<?php

namespace App\SiteTypes;

use App\Exceptions\SSHError;
use App\Models\Database;
use App\Models\DatabaseUser;
use App\Models\Site;
use Closure;
use Illuminate\Validation\Rule;

class Wordpress extends PHPSite
{
    public static function id(): string
    {
        return 'wordpress';
    }

    public static function make(): self
    {
        return new self(new Site(['type' => self::id()]));
    }

    public function language(): string
    {
        return 'php';
    }

    public function createRules(array $input): array
    {
        return [
            'php_version' => [
                'required',
                Rule::in($this->site->server->installedPHPVersions()),
            ],
            'title' => 'required',
            'username' => 'required',
            'password' => 'required',
            'email' => [
                'required',
                'email',
            ],
            'database' => [
                'required',
                Rule::exists('databases', 'id')->where(fn ($query) => $query->where('server_id', $this->site->server_id)),
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! $this->site->server->database()) {
                        $fail(__('Database is not installed'));
                    }
                },
            ],
            'database_user' => [
                'required',
                Rule::exists('database_users', 'id')->where(fn ($query) => $query->where('server_id', $this->site->server_id)),
            ],
        ];
    }

    public function createFields(array $input): array
    {
        return [
            'web_directory' => '',
            'php_version' => $input['php_version'],
        ];
    }

    public function data(array $input): array
    {
        /** @var Database $database */
        $database = $this->site->server->databases()
            ->where('id', $input['database'])
            ->firstOrFail();

        /** @var DatabaseUser $databaseUser */
        $databaseUser = $this->site->server->databaseUsers()
            ->where('id', $input['database_user'])
            ->firstOrFail();

        return [
            'url' => $this->site->getUrl(),
            'title' => $input['title'],
            'username' => $input['username'],
            'email' => $input['email'],
            'password' => $input['password'],
            'database' => $database->name,
            'database_user' => $databaseUser->username,
            'database_password' => $databaseUser->password,
        ];
    }

    /**
     * @throws SSHError
     */
    public function install(): void
    {
        $this->isolate();

        $this->site->webserver()->createVHost($this->site);
        $this->progress(30);

        $this->site->php()?->restart();
        $this->progress(60);

        $this->site->server->ssh($this->site->user)->exec(
            view('ssh.wordpress.install', [
                'path' => $this->site->path,
                'domain' => $this->site->domain,
                'isIsolated' => $this->site->isIsolated() ? 'true' : 'false',
                'isolatedUsername' => $this->site->user,
                'dbName' => $this->site->type_data['database'],
                'dbUser' => $this->site->type_data['database_user'],
                'dbPass' => $this->site->type_data['database_password'],
                'dbHost' => 'localhost',
                'dbPrefix' => 'wp_',
                'username' => $this->site->type_data['username'],
                'password' => $this->site->type_data['password'],
                'email' => $this->site->type_data['email'],
                'title' => $this->site->type_data['title'],
            ]),
            'install-wordpress',
            $this->site->id
        );
    }
}
