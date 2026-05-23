<?php

namespace App\SiteTypes;

use App\Actions\Worker\CreateWorker;
use App\Actions\Worker\ManageWorker;
use App\Enums\NodePackageManager;
use App\Exceptions\FailedToDeployGitKey;
use App\Exceptions\SSHError;
use App\Models\Site;
use App\Models\SourceControl;
use App\Models\Worker;
use App\Tooling\BunTooling;
use App\Tooling\NodeTooling;
use App\Tooling\PnpmTooling;
use App\Tooling\ToolingRegistry;
use App\Tooling\YarnTooling;
use Illuminate\Validation\Rule;

class MiseNodeJS extends MiseSiteType
{
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
        $rules = [
            'source_control' => SourceControl::siteValidationRules($this->site->server),
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
                Rule::in(NodeTooling::supportedVersions()),
            ],
            'package_manager' => [
                'required',
                Rule::in(NodePackageManager::toolIds()),
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

        // When the chosen package manager is a tool other than Node (npm comes
        // bundled with Node), its version is required and validated against
        // the tool's supported versions.
        $pmToolId = $input['package_manager'] ?? null;
        if (is_string($pmToolId) && $pmToolId !== 'node') {
            $tool = ToolingRegistry::find($pmToolId);
            if ($tool !== null) {
                $rules[$pmToolId.'_version'] = [
                    'required',
                    Rule::in($tool::supportedVersions()),
                ];
            }
        }

        return $rules;
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
        // Form sends `package_manager` as a tool id ('node' / 'pnpm' / 'yarn');
        // type_data persists the enum value ('npm' / 'pnpm' / 'yarn') for
        // backwards compatibility with existing sites and the command helpers.
        $pmToolId = $input['package_manager'] ?? 'node';
        try {
            $packageManager = NodePackageManager::fromToolId($pmToolId);
        } catch (\InvalidArgumentException) {
            $packageManager = NodePackageManager::Npm;
            $pmToolId = 'node';
        }

        $data = [
            'node_version' => $input['node_version'] ?? '22',
            'package_manager' => $packageManager->value,
            'build_command' => ! empty($input['build_command']) ? $input['build_command'] : $packageManager->buildCommand(),
            'start_command' => ! empty($input['start_command']) ? $input['start_command'] : $packageManager->startCommand(),
        ];

        // Reflect Mise-managed package manager versions into type_data so the
        // Tooling system can pick them up via `Site::existingRuntimeVersionForUser`.
        // npm is bundled with Node — no separate version row.
        foreach ([BunTooling::id(), PnpmTooling::id(), YarnTooling::id()] as $managedId) {
            if ($pmToolId === $managedId) {
                $data[$managedId.'_version'] = $input[$managedId.'_version'] ?? 'none';
            }
        }

        return $data;
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
        $this->progress(0, 'isolating-user');
        $this->isolate();
        $this->progress(10, 'setting-up-runtime');

        $this->setupRuntime();
        $this->setupPackageManager();
        $this->progress(25, 'creating-vhost');

        $this->site->webserver()->createVHost($this->site);
        $this->progress(35, 'deploying-ssh-key');

        $this->deployKey();
        $this->progress(45, 'cloning-repository');

        $this->cloneRepository();
        $this->progress(55, 'installing-dependencies');

        $this->runPackageManagerInstall();
        $this->progress(70, 'building');

        $this->runPackageManagerBuild();
        $this->progress(85, 'creating-worker');

        $this->createWorker();
        $this->progress(90, 'finishing');
    }

    /**
     * @throws SSHError
     */
    /**
     * Install the chosen package manager via the Tooling system (Mise) so it
     * participates in the site's lockstep / sibling propagation. npm is
     * bundled with Node, so when the package manager is npm we skip the
     * extra install.
     *
     * @throws SSHError
     */
    protected function setupPackageManager(): void
    {
        $pmToolId = $this->packageManager()->toolId();

        if ($pmToolId === $this->runtime()) {
            return;
        }

        $tool = ToolingRegistry::find($pmToolId);
        if ($tool === null) {
            return;
        }

        $version = $this->site->type_data[$pmToolId.'_version'] ?? 'none';
        if (! is_string($version) || $version === '' || $version === 'none') {
            return;
        }

        $tool->install($this->site, $version);
    }

    /**
     * @throws SSHError
     */
    protected function runPackageManagerInstall(): void
    {
        $packageManager = $this->packageManager();

        $this->site->ssh()->exec(
            'cd '.escapeshellarg($this->site->path).' && '.$packageManager->installCommand(),
            $packageManager->value.'-install',
            $this->site->id
        );
    }

    /**
     * @throws SSHError
     */
    protected function runPackageManagerBuild(): void
    {
        $this->site->ssh()->exec(
            'cd '.escapeshellarg($this->site->path).' && '.$this->buildCommand(),
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
