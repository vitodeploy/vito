<?php

namespace App\SiteTypes;

use App\Actions\Worker\CreateWorker;
use App\Actions\Worker\ManageWorker;
use App\Enums\NodePackageManager;
use App\Exceptions\FailedToDeployGitKey;
use App\Exceptions\SSHError;
use App\Models\Site;
use App\Models\Worker;
use App\SSH\OS\Git;
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
        return 'mise_nodejs';
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
        return 'nodejs';
    }

    protected function runtime(): string
    {
        return 'node';
    }

    protected function runtimeVersion(): string
    {
        return $this->site->type_data['node_version'] ?? '22';
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
            'package_manager' => [
                'required',
                Rule::in(array_column(NodePackageManager::cases(), 'value')),
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
        $packageManager = NodePackageManager::tryFrom($input['package_manager'] ?? '') ?? NodePackageManager::Npm;

        return [
            'node_version' => $input['node_version'] ?? '22',
            'package_manager' => $packageManager->value,
            'build_command' => ! empty($input['build_command']) ? $input['build_command'] : $packageManager->buildCommand(),
            'start_command' => ! empty($input['start_command']) ? $input['start_command'] : $packageManager->startCommand(),
        ];
    }

    protected function packageManager(): NodePackageManager
    {
        $value = $this->site->type_data['package_manager'] ?? NodePackageManager::Npm->value;

        return NodePackageManager::from($value);
    }

    protected function buildCommand(): string
    {
        return $this->site->type_data['build_command'] ?? $this->packageManager()->buildCommand();
    }

    protected function startCommand(): string
    {
        return $this->site->type_data['start_command'] ?? $this->packageManager()->startCommand();
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
        $this->setupPackageManager();
        $this->progress(25);

        $this->site->webserver()->createVHost($this->site);
        $this->progress(35);

        $this->deployKey();
        $this->progress(45);

        app(Git::class)->clone($this->site);
        $this->progress(55);

        $this->runPackageManagerInstall();
        $this->progress(70);

        $this->runPackageManagerBuild();
        $this->progress(85);

        $this->createWorker();
        $this->progress(100);
    }

    /**
     * @throws SSHError
     */
    protected function setupPackageManager(): void
    {
        $packageManager = $this->packageManager();

        if ($packageManager === NodePackageManager::Npm) {
            return;
        }

        $this->site->server->ssh($this->site->user)->exec(
            $this->wrapCommand('npm install -g '.$packageManager->value),
            'install-'.$packageManager->value,
            $this->site->id
        );
    }

    /**
     * @throws SSHError
     */
    protected function runPackageManagerInstall(): void
    {
        $packageManager = $this->packageManager();

        $this->site->server->ssh($this->site->user)->exec(
            $this->wrapCommand($packageManager->installCommand(), true),
            $packageManager->value.'-install',
            $this->site->id
        );
    }

    /**
     * @throws SSHError
     */
    protected function runPackageManagerBuild(): void
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
