<?php

namespace App\SiteTypes;

use App\DTOs\SocketEventDTO;
use App\Events\SocketEvent;
use App\Exceptions\FailedToDeployGitKey;
use App\Exceptions\SSHCommandError;
use App\Exceptions\SSHError;
use App\Helpers\SiteShellEnvironment;
use App\Http\Resources\SiteResource;
use App\Models\Deployment;
use App\Models\Service;
use App\Models\Site;
use App\Services\PHP\PHP;
use App\SSH\OS\Git;
use App\Tooling\ToolingRegistry;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

abstract class AbstractSiteType implements SiteType
{
    public function __construct(protected Site $site) {}

    abstract public static function make(): self;

    /**
     * Tooling IDs (matching `App\Tooling\ToolingRegistry`) that this site type
     * offers at create time. Drives the `tooling` DynamicField, validation
     * rules, type_data extraction and the post-isolate install loop.
     *
     * @return array<int, string>
     */
    public static function createTimeTools(): array
    {
        return [];
    }

    /**
     * Whether this site type stores Tooling state in `type_data` and accepts
     * sibling-propagation writes from the Tooling system. PHP-family and
     * Mise-family site types are tooling-aware; LoadBalancer / deprecated
     * NodeJS / PHPMyAdmin / WordPress are not.
     */
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
     * Extra environment variables to inject into deployment scripts. Default
     * returns the merged PATH contributions from every Tooling installed for
     * the site's isolated user — `SiteShellEnvironment::collect()` is a
     * no-op for sites without an isolated user, so this is safe for all
     * site types. Override only if you need additional vars on top.
     *
     * @return array<string, string>
     */
    public function deploymentEnvironment(): array
    {
        return SiteShellEnvironment::collect($this->site);
    }

    /**
     * Default no-op. Site types override to hook into the post-deploy
     * lifecycle (e.g. AbstractProxiedSiteType lazy-creates the supervisor
     * worker on first successful deploy).
     */
    public function afterDeploy(Deployment $deployment): void
    {
        //
    }

    /**
     * Default deploy-script content. Reads from `resources/deployment-scripts/{id}.sh`
     * if present (preserving the legacy convention for PHPSite / Laravel / etc.);
     * site types that compose their script programmatically override this.
     */
    public function defaultDeploymentScript(): string
    {
        $path = resource_path('deployment-scripts/'.static::id().'.sh');

        return File::exists($path) ? File::get($path) : '';
    }

    /**
     * Return null to support all webservers.
     *
     * @return string[]|null
     */
    public function supportedWebservers(): ?array
    {
        return null;
    }

    /**
     * Return a Mustache template string to completely replace the default webserver vhost template.
     * Return null to use the built-in template.
     */
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
        if ($this->site->sourceControl?->isGithubApp()) {
            return;
        }

        $os = $this->site->server->os();

        if (! $this->site->ssh_key) {
            $keyName = $this->site->getSshKeyName();
            $os->generateSSHKey($keyName, $this->site);
            $publicKey = $os->readSSHKey($keyName, $this->site);

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
            $keyId = $this->site->sourceControl?->provider()?->deployKey(
                $this->site->getDeployKeyName(),
                $this->site->repository,
                $this->site->ssh_key
            );
            $this->site->jsonUpdate('type_data', 'deploy_key_id', $keyId);
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

        $lock = $this->site->isolatedUser?->lock() ?? $this->site->server->isolatedUserLock($this->site->user);

        try {
            $lock->block(30);
        } catch (LockTimeoutException) {
            throw new RuntimeException("Could not acquire isolated-user lock for '{$this->site->user}' on server {$this->site->server_id} within 30s.");
        }

        try {
            $this->site->server->os()->createIsolatedUser(
                $this->site->user,
                Str::random(15),
                $this->site->id
            );

            if ($this->site->php_version) {
                $service = $this->site->php();
                if (! $service instanceof Service) {
                    throw new RuntimeException('PHP service not found');
                }
                if (! $this->site->fpmPoolSharedWithSiblings() && ! $this->fpmPoolExists($this->site->user, $this->site->php_version)) {
                    /** @var PHP $php */
                    $php = $service->handler();
                    $php->createFpmPool(
                        $this->site->user,
                        $this->site->php_version
                    );
                }
            }
        } finally {
            $lock->release();
        }
    }

    /**
     * @throws SSHError
     */
    protected function cloneRepository(): void
    {
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
    protected function fpmPoolExists(string $user, string $version): bool
    {
        try {
            $this->site->server->ssh()->exec(view('ssh.site.check-fpm-pool-exists', [
                'user' => $user,
                'version' => $version,
            ]));

            return true;
        } catch (SSHCommandError) {
            return false;
        }
    }

    /**
     * Install every tool the site type offers at create time whose requested
     * version (passed through `type_data` at site creation) is non-empty and
     * isn't already installed for the isolated user. On success, the iuser's
     * `installed_tooling` is updated so siblings inherit the version.
     *
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

            $tool->install($this->site, $version);

            $iuser?->setToolingVersion($toolId, $version);

            $typeData = $this->site->type_data ?? [];
            unset($typeData[$key]);
            $this->site->type_data = $typeData;
            $this->site->save();
        }
    }
}
