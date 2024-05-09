<?php

namespace App\SiteTypes;

use App\Actions\Database\CreateDatabase;
use App\Actions\Database\CreateDatabaseUser;
use App\Actions\Database\LinkUser;
use App\Enums\SiteFeature;
use App\Models\Database;
use App\Models\DatabaseUser;
use Closure;
use Illuminate\Validation\Rule;

class Wordpress extends AbstractSiteType
{
    public function language(): string
    {
        return 'php';
    }

    public function supportedFeatures(): array
    {
        return [
            SiteFeature::SSL,
        ];
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
            'email' => 'required|email',
            'database' => [
                'required',
                Rule::unique('databases', 'name')->where(function ($query) {
                    return $query->where('server_id', $this->site->server_id);
                }),
                function (string $attribute, mixed $value, Closure $fail) {
                    if (! $this->site->server->database()) {
                        $fail(__('Database is not installed'));
                    }
                },
            ],
            'database_user' => [
                'required',
                Rule::unique('database_users', 'username')->where(function ($query) {
                    return $query->where('server_id', $this->site->server_id);
                }),
            ],
            'database_password' => 'required',
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
        return [
            'url' => $this->site->getUrl(),
            'title' => $input['title'],
            'username' => $input['username'],
            'email' => $input['email'],
            'password' => $input['password'],
            'database' => $input['database'],
            'database_user' => $input['database_user'],
            'database_password' => $input['database_password'],
        ];
    }

    public function install(): void
    {
        /** @var Webserver $webserver */
        $webserver = $this->site->server->webserver()->handler();
        $webserver->createVHost($this->site);
        $this->progress(30);
        /** @var Database $database */
        $database = app(CreateDatabase::class)->create($this->site->server, [
            'name' => $this->site->type_data['database'],
        ]);
        /** @var DatabaseUser $databaseUser */
        $databaseUser = app(CreateDatabaseUser::class)->create($this->site->server, [
            'username' => $this->site->type_data['database_user'],
            'password' => $this->site->type_data['database_password'],
            'remote' => false,
            'host' => 'localhost',
        ], [$database->name]);
        app(LinkUser::class)->link($databaseUser, [
            'databases' => [$database->name],
        ]);
        $this->site->php()?->restart();
        $this->progress(60);
        app(\App\SSH\Wordpress\Wordpress::class)->install($this->site);
    }

    public function editRules(array $input): array
    {
        return [
            'title' => 'required',
            'url' => 'required',
        ];
    }

    public function edit(): void
    {
        //
    }
}
