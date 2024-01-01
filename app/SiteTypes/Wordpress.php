<?php

namespace App\SiteTypes;

use App\Enums\SiteFeature;
use App\Jobs\Site\CreateVHost;
use App\Jobs\Site\InstallWordpress;
use App\Models\Database;
use App\Models\DatabaseUser;
use App\SSHCommands\Wordpress\UpdateWordpressCommand;
use Closure;
use Illuminate\Support\Facades\Bus;
use Illuminate\Validation\Rule;
use Throwable;

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

    public function createValidationRules(array $input): array
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
            'url' => $this->site->url,
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
        $chain = [
            new CreateVHost($this->site),
            $this->progress(15),
            function () {
                /** @var Database $database */
                $database = $this->site->server->databases()->create([
                    'name' => $this->site->type_data['database'],
                ]);
                $database->createOnServer('sync');
                /** @var DatabaseUser $databaseUser */
                $databaseUser = $this->site->server->databaseUsers()->create([
                    'username' => $this->site->type_data['database_user'],
                    'password' => $this->site->type_data['database_password'],
                    'databases' => [$this->site->type_data['database']],
                ]);
                $databaseUser->createOnServer('sync');
                $databaseUser->unlinkUser('sync');
                $databaseUser->linkUser('sync');
            },
            $this->progress(50),
            new InstallWordpress($this->site),
            $this->progress(75),
            function () {
                $this->site->php()?->restart();
                $this->site->installationFinished();
            },
        ];

        Bus::chain($chain)
            ->catch(function (Throwable $e) {
                $this->site->installationFailed($e);
            })
            ->onConnection('ssh-long')
            ->dispatch();
    }

    public function editValidationRules(array $input): array
    {
        return [
            'title' => 'required',
            'url' => 'required',
            // 'email' => 'required|email',
        ];
    }

    public function edit(): void
    {
        $this->site->status = 'installing';
        $this->site->progress = 90;
        $this->site->save();
        $chain = [
            function () {
                $this->site->server->ssh()->exec(
                    new UpdateWordpressCommand(
                        $this->site->path,
                        $this->site->type_data['url'],
                        $this->site->type_data['username'] ?? '',
                        $this->site->type_data['password'] ?? '',
                        $this->site->type_data['email'] ?? '',
                        $this->site->type_data['title'] ?? '',
                    ),
                    'update-wordpress',
                    $this->site->id
                );
                $this->site->installationFinished();
            },
        ];

        Bus::chain($chain)
            ->catch(function (Throwable $e) {
                $this->site->installationFailed($e);
            })
            ->onConnection('ssh')
            ->dispatch();
    }
}
