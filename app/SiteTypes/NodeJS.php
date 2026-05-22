<?php

namespace App\SiteTypes;

use App\Actions\Worker\CreateWorker;
use App\Actions\Worker\ManageWorker;
use App\Exceptions\FailedToDeployGitKey;
use App\Exceptions\SSHError;
use App\Models\Site;
use App\Models\SourceControl;
use App\Models\Worker;

class NodeJS extends AbstractSiteType
{
    public static function id(): string
    {
        return 'nodejs';
    }

    public function language(): string
    {
        return 'nodejs';
    }

    public function requiredServices(): array
    {
        return [
            'nodejs',
            'webserver',
            'process_manager',
        ];
    }

    public static function make(): self
    {
        return new self(new Site(['type' => self::id()]));
    }

    public function createRules(array $input): array
    {
        return [
            'source_control' => SourceControl::siteValidationRules(),
            'repository' => [
                'required',
            ],
            'branch' => [
                'required',
            ],
            'port' => [
                'required',
                'numeric',
                'between:1,65535',
            ],
        ];
    }

    public function createFields(array $input): array
    {
        return [
            'source_control_id' => $input['source_control'] ?? '',
            'repository' => $input['repository'] ?? '',
            'branch' => $input['branch'] ?? '',
            'port' => $input['port'] ?? '',
        ];
    }

    public function data(array $input): array
    {
        return [];
    }

    /**
     * @throws FailedToDeployGitKey
     * @throws SSHError
     */
    public function install(): void
    {
        $this->progress(0, 'isolating-user');
        $this->isolate();
        $this->progress(10, 'creating-vhost');
        $this->site->webserver()->createVHost($this->site);
        $this->progress(20, 'deploying-ssh-key');
        $this->deployKey();
        $this->progress(30, 'cloning-repository');
        $this->cloneRepository();
        $this->progress(45, 'installing-npm-dependencies');
        $this->site->server->ssh($this->site->user)->exec(
            __('npm install --prefix=:path', [
                'path' => $this->site->path,
            ]),
            'install-npm-dependencies',
            $this->site->id
        );
        $this->progress(60, 'building');
        $this->site->server->ssh($this->site->user)->exec(
            __('npm run build --prefix=:path', [
                'path' => $this->site->path,
            ]),
            'npm-build',
            $this->site->id
        );
        $this->progress(75, 'creating-worker');
        $command = __('npm start --prefix=:path', [
            'path' => $this->site->path,
        ]);
        /** @var ?Worker $worker */
        $worker = $this->site->workers()->where('name', 'app')->first();
        if ($worker) {
            app(ManageWorker::class)->restart($worker);
        } else {
            app(CreateWorker::class)->create(
                $this->site->server,
                [
                    'name' => 'app',
                    'command' => $command,
                    'user' => $this->site->user ?? $this->site->server->getSshUser(),
                    'auto_start' => true,
                    'auto_restart' => true,
                    'numprocs' => 1,
                ],
                $this->site,
            );
        }
        $this->progress(90, 'finishing');
    }

    public function baseCommands(): array
    {
        return [
            [
                'name' => 'npm:install',
                'command' => 'npm install',
            ],
        ];
    }

    public function vhostData(): array
    {
        return [
            'is_reverse_proxy' => true,
        ];
    }
}
