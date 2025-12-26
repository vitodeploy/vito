<?php

namespace App\SiteTypes;

use App\Actions\Worker\CreateWorker;
use App\Actions\Worker\ManageWorker;
use App\Exceptions\FailedToDeployGitKey;
use App\Exceptions\SSHError;
use App\Models\Site;
use App\Models\Worker;
use App\SSH\OS\Git;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;

class MiseNodeJS extends MiseSiteType
{
    public const NODE_VERSIONS = [
        '22',
        '20',
        '18',
        '16',
    ];

    public static function id(): string
    {
        return 'nodejs';
    }

    public function language(): string
    {
        return 'nodejs';
    }

    protected function runtime(): string
    {
        return 'node';
    }

    protected function runtimeVersion(): ?string
    {
        return $this->site->type_data['node_version'] ?? null;
    }

    public static function make(): self
    {
        return new self(new Site(['type' => self::id()]));
    }

    public function createRules(array $input): array
    {
        return [
            'source_control' => [
                'required',
                Rule::exists('source_controls', 'id'),
            ],
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
            'node_version' => [
                'required',
                Rule::in(self::NODE_VERSIONS),
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
        return [
            'node_version' => $input['node_version'] ?? '20',
        ];
    }

    /**
     * @throws FailedToDeployGitKey
     * @throws SSHError
     */
    public function install(): void
    {
        $this->isolate();
        $this->progress(10);

        $this->setupRuntime();
        $this->progress(25);

        $this->site->webserver()->createVHost($this->site);
        $this->progress(35);

        $this->deployKey();
        $this->progress(45);

        app(Git::class)->clone($this->site);
        $this->progress(55);

        $this->runNpmCommand('install');
        $this->progress(70);

        $this->runNpmCommand('run build');
        $this->progress(85);

        $this->createWorker();
        $this->progress(100);
    }

    /**
     * @throws SSHError
     */
    protected function runNpmCommand(string $command): void
    {
        $fullCommand = $this->runtimePrefix().' npm '.$command.' --prefix='.$this->site->path;
        $this->site->server->ssh($this->site->user)->exec(
            $fullCommand,
            'npm-'.str_replace(' ', '-', $command),
            $this->site->id
        );
    }

    protected function createWorker(): void
    {
        $command = $this->runtimePrefix().' npm start --prefix='.$this->site->path;

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
    }

    public function baseCommands(): array
    {
        return [
            [
                'name' => 'npm:install',
                'command' => $this->runtimePrefix().' npm install',
            ],
        ];
    }

    public function vhost(string $webserver): string|View
    {
        if ($webserver === 'nginx') {
            return view('ssh.services.webserver.nginx.vhost', [
                'header' => [
                    view('ssh.services.webserver.nginx.vhost-blocks.force-ssl', ['site' => $this->site]),
                ],
                'main' => [
                    view('ssh.services.webserver.nginx.vhost-blocks.port', ['site' => $this->site]),
                    view('ssh.services.webserver.nginx.vhost-blocks.core', ['site' => $this->site]),
                    view('ssh.services.webserver.nginx.vhost-blocks.reverse-proxy', ['site' => $this->site]),
                    view('ssh.services.webserver.nginx.vhost-blocks.redirects', ['site' => $this->site]),
                ],
            ]);
        }

        if ($webserver === 'caddy') {
            return view('ssh.services.webserver.caddy.vhost', [
                'site' => $this->site,
                'main' => [
                    view('ssh.services.webserver.caddy.vhost-blocks.force-ssl', ['site' => $this->site]),
                    view('ssh.services.webserver.caddy.vhost-blocks.port', ['site' => $this->site]),
                    view('ssh.services.webserver.caddy.vhost-blocks.core', ['site' => $this->site]),
                    view('ssh.services.webserver.caddy.vhost-blocks.reverse-proxy', ['site' => $this->site]),
                    view('ssh.services.webserver.caddy.vhost-blocks.redirects', ['site' => $this->site]),
                ],
            ]);
        }

        return '';
    }
}
