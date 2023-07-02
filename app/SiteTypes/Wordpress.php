<?php

namespace App\SiteTypes;

use App\Enums\SiteStatus;
use App\Events\Broadcast;
use App\Jobs\Site\CreateVHost;
use App\Jobs\Site\InstallWordpress;
use App\SSHCommands\UpdateWordpressCommand;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class Wordpress extends AbstractSiteType
{
    public function language(): string
    {
        return 'php';
    }

    public function createValidationRules(array $input): array
    {
        return [
            'title' => 'required',
            'username' => 'required',
            'password' => 'required',
            'email' => 'required|email',
            'database' => 'required',
            'database_user' => 'required',
        ];
    }

    public function createFields(array $input): array
    {
        return [
            'web_directory' => $input['web_directory'] ?? '',
        ];
    }

    public function data(array $input): array
    {
        $data = $this->site->type_data;
        $data['url'] = $this->site->url;
        if (isset($input['title']) && $input['title']) {
            $data['title'] = $input['title'];
        }
        if (isset($input['username']) && $input['username']) {
            $data['username'] = $input['username'];
        }
        if (isset($input['email']) && $input['email']) {
            $data['email'] = $input['email'];
        }
        if (isset($input['password']) && $input['password']) {
            $data['password'] = $input['password'];
        }
        if (isset($input['database']) && $input['database']) {
            $data['database'] = $input['database'];
        }
        if (isset($input['database_user']) && $input['database_user']) {
            $data['database_user'] = $input['database_user'];
        }
        if (isset($input['url']) && $input['url']) {
            $data['url'] = $input['url'];
        }

        return $data;
    }

    public function install(): void
    {
        $chain = [
            new CreateVHost($this->site),
            $this->progress(30),
            new InstallWordpress($this->site),
            $this->progress(65),
            function () {
                $this->site->php()?->restart();
            },
        ];

        $chain[] = function () {
            $this->site->update([
                'status' => SiteStatus::READY,
                'progress' => 100,
            ]);
            event(
                new Broadcast('install-site-finished', [
                    'site' => $this->site,
                ])
            );
            /** @todo notify */
        };

        Bus::chain($chain)
            ->catch(function (Throwable $e) {
                $this->site->update([
                    'status' => SiteStatus::INSTALLATION_FAILED,
                ]);
                event(
                    new Broadcast('install-site-failed', [
                        'site' => $this->site,
                    ])
                );
                /** @todo notify */
                Log::error('install-site-error', [
                    'error' => (string) $e,
                ]);
                throw $e;
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
                $this->site->update([
                    'status' => SiteStatus::READY,
                ]);
                event(
                    new Broadcast('install-site-finished', [
                        'site' => $this->site,
                    ])
                );
            },
        ];

        Bus::chain($chain)
            ->catch(function (Throwable $e) {
                $this->site->update([
                    'status' => SiteStatus::INSTALLATION_FAILED,
                ]);
                event(
                    new Broadcast('install-site-failed', [
                        'site' => $this->site,
                    ])
                );
                /** @todo notify */
                Log::error('install-site-error', [
                    'error' => (string) $e,
                ]);
                throw $e;
            })
            ->onConnection('ssh')
            ->dispatch();
    }
}
