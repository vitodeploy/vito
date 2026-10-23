<?php

namespace App\SiteTypes;

use App\Actions\IsolatedUser\EnsureIsolatedUser;
use App\Actions\PHP\EnsureFpmPool;
use App\Actions\SourceControl\SetupDeployKey;
use App\DTOs\SocketEventDTO;
use App\DTOs\ToolingTargetDTO;
use App\Events\SocketEvent;
use App\Exceptions\FailedToDeployGitKey;
use App\Exceptions\SSHCommandError;
use App\Exceptions\SSHError;
use App\Helpers\SiteShellEnvironment;
use App\Http\Resources\SiteResource;
use App\Models\Deployment;
use App\Models\Site;
use App\SSH\OS\Git;
use App\Tooling\SiteToolingState;
use App\Tooling\ToolingRegistry;
use Illuminate\Support\Facades\File;

abstract class AbstractSiteType implements SiteType
{
    public function __construct(protected Site $site) {}

    abstract public static function make(): self;

    /**
     * @return array<int, string>
     */
    public static function createTimeTools(): array
    {
        return [];
    }

    /**
     * @return array<int, string>
     */
    public static function requiredTooling(): array
    {
        return [];
    }

    public static function supportsTooling(): bool
    {
        return false;
    }

    public function createRules(array $input): array
    {
        return [];
    }

    public function createFields(array $input): array
    {
        return [];
    }

    public function data(array $input): array
    {
        return [];
    }

    public function baseCommands(): array
    {
        return [];
    }

    public function vhostData(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    public function deploymentEnvironment(): array
    {
        return SiteShellEnvironment::collect(ToolingTargetDTO::forSite($this->site));
    }

    public function afterDeploy(Deployment $deployment): void
    {
        //
    }

    public function assertReadyToDeploy(): void
    {
        //
    }

    public function defaultDeploymentScript(): string
    {
        $path = resource_path('deployment-scripts/'.static::id().'.sh');

        return File::exists($path) ? File::get($path) : '';
    }

    /**
     * @return string[]|null
     */
    public function supportedWebservers(): ?array
    {
        return null;
    }

    public function vhostTemplate(string $webserver): ?string
    {
        return null;
    }

    protected function progress(int $percentage, ?string $step): void
    {
        $this->site->progress = $percentage;
        $this->site->progress_step = $step;
        $this->site->save();

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $this->site->server->project_id,
            type: 'site.updated',
            data: new SiteResource($this->site),
        ));
    }

    /**
     * @throws FailedToDeployGitKey
     * @throws SSHError
     */
    protected function deployKey(): void
    {
        if (! $this->site->sourceControl) {
            return;
        }

        if ($this->site->sourceControl->isGithubApp()) {
            return;
        }

        $setupDeployKey = app(SetupDeployKey::class);
        $keyName = $this->site->getSshKeyName();
        $user = $this->site->user ?? $this->site->server->getSshUser();

        if (! $this->site->ssh_key) {
            $publicKey = $setupDeployKey->generateKey(
                $this->site->server,
                $user,
                $keyName,
                $this->site->id,
            );

            if (str_starts_with($keyName, 'iuser_') && $this->site->isolatedUser) {
                $this->site->isolatedUser->ssh_key = $publicKey;
                $this->site->isolatedUser->save();
                $this->site->setRelation('isolatedUser', $this->site->isolatedUser->fresh());
            } else {
                $this->site->ssh_key = $publicKey;
                $this->site->save();
            }
        }

        if (empty($this->site->type_data['deploy_key_id'])) {
            $key = $setupDeployKey->setup(
                $this->site->server,
                $user,
                $keyName,
                $this->site->sourceControl,
                $this->site->repository,
                $this->site->getDeployKeyName(),
                publicKey: $this->site->ssh_key,
                siteId: $this->site->id,
            );
            $this->site->jsonUpdate('type_data', 'deploy_key_id', $key['deploy_key_id']);
        }
    }

    /**
     * @throws SSHError
     */
    protected function isolate(): void
    {
        if (! $this->site->isIsolated()) {
            return;
        }

        app(EnsureIsolatedUser::class)->ensure($this->site->server, $this->site->user, $this->site->id);

        if ($this->site->php_version) {
            app(EnsureFpmPool::class)->ensure($this->site->server, $this->site->user, $this->site->php_version);
        }
    }

    /**
     * @throws SSHError
     */
    protected function cloneRepository(): void
    {
        if (! $this->site->repository) {
            return;
        }

        if ($this->repositoryAlreadyCloned()) {
            return;
        }
        app(Git::class)->clone($this->site);
    }

    /**
     * @throws SSHError
     */
    protected function repositoryAlreadyCloned(): bool
    {
        try {
            $this->site->server->ssh($this->site->user)->exec(view('ssh.site.check-repository-cloned', [
                'path' => $this->site->path,
            ]));

            return true;
        } catch (SSHCommandError) {
            return false;
        }
    }

    /**
     * @throws SSHError
     */
    protected function setupRequestedTooling(): void
    {
        $iuser = $this->site->isolatedUser;

        foreach (static::createTimeTools() as $toolId) {
            $tool = ToolingRegistry::find($toolId);
            if (! $tool) {
                continue;
            }

            $key = $tool::typeDataKey();
            $version = $this->site->type_data[$key] ?? 'none';
            if ($version === 'none' || $version === '') {
                continue;
            }

            $existing = $iuser?->toolingVersion($toolId);

            if ($existing === $version) {
                continue;
            }

            $tool->install(ToolingTargetDTO::forSite($this->site), $version);

            SiteToolingState::completeInstall($this->site, $toolId, $version);

            $typeData = $this->site->type_data ?? [];
            unset($typeData[$key]);
            $this->site->type_data = $typeData;
            $this->site->save();
        }
    }
}
