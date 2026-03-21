<?php

namespace App\SiteTypes;

use App\Actions\Worker\CreateWorker;
use App\Actions\Worker\ManageWorker;
use App\Exceptions\FailedToDeployGitKey;
use App\Exceptions\SSHError;
use App\Models\Site;
use App\Models\Worker;
use App\SSH\OS\Git;
use Illuminate\Validation\Rule;

class MiseBun extends MiseSiteType
{
    public const BUN_VERSIONS = [
        '1.2',
        '1.1',
        '1.0',
    ];

    public static function id(): string
    {
        return 'mise_bun';
    }

    public function requiredServices(): array
    {
        return [
            'webserver',
            'process_manager',
        ];
    }

    public function language(): string
    {
        return 'bun';
    }

    protected function runtime(): string
    {
        return 'bun';
    }

    protected function runtimeVersion(): string
    {
        return $this->site->type_data['bun_version'] ?? '1.2';
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
            'bun_version' => [
                'required',
                Rule::in(self::BUN_VERSIONS),
            ],
            'build_command' => [
                'nullable',
                'string',
            ],
            'start_command' => [
                'nullable',
                'string',
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
            'bun_version' => $input['bun_version'] ?? '1.2',
            'build_command' => ! empty($input['build_command']) ? $input['build_command'] : 'bun run build',
            'start_command' => ! empty($input['start_command']) ? $input['start_command'] : 'bun run start',
        ];
    }

    protected function buildCommand(): string
    {
        return $this->site->type_data['build_command'] ?? 'bun run build';
    }

    protected function startCommand(): string
    {
        return $this->site->type_data['start_command'] ?? 'bun run start';
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

        $this->runInstall();
        $this->progress(70);

        $this->runBuild();
        $this->progress(85);

        $this->createWorker();
        $this->progress(100);
    }

    /**
     * @throws SSHError
     */
    protected function runInstall(): void
    {
        $this->site->server->ssh($this->site->user)->exec(
            $this->wrapCommand('bun install --frozen-lockfile', true),
            'bun-install',
            $this->site->id
        );
    }

    /**
     * @throws SSHError
     */
    protected function runBuild(): void
    {
        $this->site->server->ssh($this->site->user)->exec(
            $this->wrapCommand($this->buildCommand(), true),
            'build',
            $this->site->id
        );
    }

    protected function createWorker(): void
    {
        /** @var ?Worker $worker */
        $worker = $this->site->workers()->where('name', 'app')->first();
        if ($worker) {
            app(ManageWorker::class)->restart($worker);
        } else {
            app(CreateWorker::class)->create(
                $this->site->server,
                [
                    'name' => 'app',
                    'command' => $this->workerCommand(),
                    'user' => $this->site->user ?? $this->site->server->getSshUser(),
                    'auto_start' => true,
                    'auto_restart' => true,
                    'numprocs' => 1,
                    'environment' => $this->workerEnvironment(),
                ],
                $this->site,
            );
        }
    }

    public function baseCommands(): array
    {
        return [];
    }

    public function vhostData(): array
    {
        return [
            'is_reverse_proxy' => true,
        ];
    }
}
