<?php

namespace App\Models;

use App\Enums\DeploymentStatus;
use App\Enums\HostedDomainStatus;
use App\Enums\HostedDomainType;
use App\Enums\RedirectStatus;
use App\Enums\SiteStatus;
use App\Enums\SslStatus;
use App\Enums\WorkerStatus;
use App\Exceptions\SourceControlIsNotConnected;
use App\Exceptions\SSHError;
use App\Helpers\SiteShellEnvironment;
use App\Helpers\SSH;
use App\Jobs\SSL\DeleteSiteSslJob;
use App\Services\Webserver\Webserver;
use App\SiteFeatures\ActionInterface;
use App\SiteTypes\AbstractProxiedSiteType;
use App\SiteTypes\AbstractSiteType;
use App\SiteTypes\BunSite;
use App\SiteTypes\NodeSite;
use App\SiteTypes\SiteType;
use App\SourceControlProviders\GithubApp;
use App\Tooling\ToolingRegistry;
use App\Traits\HasProjectThroughServer;
use Database\Factories\SiteFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * @property int $server_id
 * @property string $type
 * @property array<string, mixed> $type_data
 * @property ?array<int, array{key: string, value: string, is_secret: bool}> $env_variables
 * @property ?array<int, array{key: string, value: string, is_secret: bool}> $worker_environment
 * @property string $domain
 * @property array<int, string> $aliases
 * @property string $web_directory
 * @property string $path
 * @property string $php_version
 * @property string $source_control
 * @property int $source_control_id
 * @property string $repository
 * @property string $ssh_key
 * @property string $branch
 * @property SiteStatus $status
 * @property int $port
 * @property int $progress
 * @property ?string $progress_step
 * @property ?string $last_error
 * @property ?int $isolated_user_id
 * @property ?IsolatedUser $isolatedUser
 * @property ?string $user
 * @property bool $force_ssl
 * @property bool $ssl_enabled
 * @property ?string $vhost_template
 * @property bool $vhost_generation_enabled
 * @property ?string $verification_key
 * @property Server $server
 * @property Collection<int, ServerLog> $logs
 * @property Collection<int, Deployment> $deployments
 * @property Collection<int, Command> $commands
 * @property ?GitHook $gitHook
 * @property Collection<int, DeploymentScript> $deploymentScripts
 * @property ?DeploymentScript $deploymentScript
 * @property ?DeploymentScript $buildScript
 * @property ?DeploymentScript $preFlightScript
 * @property Collection<int, Worker> $workers
 * @property Collection<int, Ssl> $ssls
 * @property string $ssh_key_name
 * @property ?SourceControl $sourceControl
 * @property Collection<int, LoadBalancerServer> $loadBalancerServers
 * @property Project $project
 * @property Collection<int, Redirect> $redirects
 * @property Collection<int, Redirect> $activeRedirects
 */
class Site extends AbstractModel
{
    /** @use HasFactory<SiteFactory> */
    use HasFactory;

    use HasProjectThroughServer;

    protected $fillable = [
        'server_id',
        'isolated_user_id',
        'type',
        'type_data',
        'env_variables',
        'domain',
        'aliases',
        'web_directory',
        'path',
        'php_version',
        'source_control',
        'source_control_id',
        'repository',
        'ssh_key',
        'branch',
        'status',
        'port',
        'progress',
        'user',
        'force_ssl',
        'ssl_enabled',
        'vhost_template',
        'vhost_generation_enabled',
        'verification_key',
    ];

    protected $with = ['isolatedUser'];

    protected $casts = [
        'server_id' => 'integer',
        'isolated_user_id' => 'integer',
        'type_data' => 'json',
        'env_variables' => 'encrypted:array',
        'worker_environment' => 'encrypted:array',
        'port' => 'integer',
        'progress' => 'integer',
        'aliases' => 'array',
        'source_control_id' => 'integer',
        'force_ssl' => 'boolean',
        'ssl_enabled' => 'boolean',
        'vhost_generation_enabled' => 'boolean',
        'status' => SiteStatus::class,
    ];

    public static function boot(): void
    {
        parent::boot();

        static::deleting(function (Site $site): void {
            $site->workers()->each(function ($worker): void {
                /** @var Worker $worker */
                $worker->delete();
            });
            $site->ssls()->each(function (Ssl $ssl) use ($site): void {
                dispatch(new DeleteSiteSslJob($site->server, $ssl))->onQueue('ssh');
            });
            $site->deployments()->delete();
            $site->deploymentScript()->delete();
            $site->gitHook?->destroyHook();
        });

        static::created(function (Site $site): void {
            $site->createDefaultDeploymentScript();
        });
    }

    public function isReady(): bool
    {
        return $this->status === SiteStatus::READY;
    }

    public function ssh(): SSH
    {
        return $this->server->ssh($this->user)->variables(
            SiteShellEnvironment::collect($this)
        );
    }

    public function isInstalling(): bool
    {
        return in_array($this->status, [SiteStatus::INSTALLING, SiteStatus::INSTALLATION_FAILED]);
    }

    public function isInstallationFailed(): bool
    {
        return $this->status === SiteStatus::INSTALLATION_FAILED;
    }

    /**
     * @return array<int, array{key: string, ...}>
     */
    public function getWarnings(): array
    {
        $warnings = [];

        $hostedDomains = $this->relationLoaded('hostedDomains') ? $this->hostedDomains : collect();

        $pendingDomains = $hostedDomains->where('status', HostedDomainStatus::PENDING);
        if ($pendingDomains->isNotEmpty()) {
            $warnings[] = [
                'key' => 'pending_domains',
                'count' => $pendingDomains->count(),
                'domains' => $pendingDomains->pluck('domain')->all(),
            ];
        }

        if (! $this->ssl_enabled) {
            $warnings[] = ['key' => 'ssl_disabled'];
        }

        if (! $this->vhost_generation_enabled) {
            $warnings[] = ['key' => 'vhost_generation_disabled'];
        }

        $expiring = $hostedDomains->filter(
            fn ($hd) => $hd->ssl_id
                && $hd->relationLoaded('ssl')
                && $hd->ssl
                && $hd->ssl->status === SslStatus::CREATED
                && $hd->ssl->expires_at
                && $hd->ssl->expires_at <= now()->addDays(14)
        );

        if ($expiring->isNotEmpty()) {
            $earliestExpiry = $expiring->min(fn ($hd) => $hd->ssl->expires_at);
            $warnings[] = [
                'key' => 'ssl_expiring',
                'count' => $expiring->count(),
                'domains' => $expiring->pluck('domain')->all(),
                'earliest_expiry' => $earliestExpiry?->toIso8601String(),
            ];
        }

        if ($this->type() instanceof AbstractProxiedSiteType
            && ! $this->deployments()->where('status', DeploymentStatus::FINISHED)->exists()) {
            $warnings[] = ['key' => 'needs_first_deploy'];
        }

        if ($this->relationLoaded('workers')) {
            $bootstrapId = $this->bootstrapWorkerId();

            foreach ($this->workers as $worker) {
                $isBootstrap = $bootstrapId !== null && $worker->id === $bootstrapId;

                $inError = $worker->status === WorkerStatus::FAILED
                    || ($isBootstrap && $worker->status === WorkerStatus::STOPPED);

                if (! $inError) {
                    continue;
                }

                $warnings[] = [
                    'key' => 'worker_not_running',
                    'worker_id' => $worker->id,
                    'name' => $worker->name,
                    'status' => $worker->status->getText(),
                    'status_color' => $worker->status->getColor(),
                    'error' => $worker->error,
                ];
            }
        }

        return $warnings;
    }

    public function bootstrapWorkerId(): ?int
    {
        $storedId = $this->type_data['bootstrap_worker_id'] ?? null;
        if (is_int($storedId)) {
            return $storedId;
        }

        return (is_string($storedId) && ctype_digit($storedId)) ? (int) $storedId : null;
    }

    /**
     * @return BelongsTo<Server, covariant $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * @return BelongsTo<IsolatedUser, covariant $this>
     */
    public function isolatedUser(): BelongsTo
    {
        return $this->belongsTo(IsolatedUser::class);
    }

    public function getUserAttribute(?string $value): ?string
    {
        return ($value !== null && $value !== '') ? $value : $this->isolatedUser?->username;
    }

    public function getSshKeyAttribute(?string $value): ?string
    {
        return ($value !== null && $value !== '') ? $value : $this->isolatedUser?->ssh_key;
    }

    /**
     * @return HasMany<ServerLog, covariant $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(ServerLog::class);
    }

    /**
     * @return HasMany<Deployment, covariant $this>
     */
    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
    }

    /**
     * @return HasMany<Command, covariant $this>
     */
    public function commands(): HasMany
    {
        return $this->hasMany(Command::class);
    }

    /**
     * @return HasOne<GitHook, covariant $this>
     */
    public function gitHook(): HasOne
    {
        return $this->hasOne(GitHook::class);
    }

    /**
     * @return HasMany<DeploymentScript, covariant $this>
     */
    public function deploymentScripts(): HasMany
    {
        return $this->hasMany(DeploymentScript::class);
    }

    /**
     * @return HasOne<DeploymentScript, covariant $this>
     */
    public function deploymentScript(): HasOne
    {
        return $this->hasOne(DeploymentScript::class, 'site_id')->where('name', 'default');
    }

    /**
     * @return HasOne<DeploymentScript, covariant $this>
     */
    public function buildScript(): HasOne
    {
        return $this->hasOne(DeploymentScript::class, 'site_id')->where('name', 'build');
    }

    /**
     * @return HasOne<DeploymentScript, covariant $this>
     */
    public function preFlightScript(): HasOne
    {
        return $this->hasOne(DeploymentScript::class, 'site_id')->where('name', 'pre-flight');
    }

    public function ensureDeploymentScriptsExist(): void
    {
        $created = false;

        if ($this->modernDeploymentEnabled()) {
            if (! $this->buildScript) {
                $this->deploymentScripts()->create([
                    'name' => 'build',
                    'content' => '',
                ]);
                $created = true;
            }
            if (! $this->preFlightScript) {
                $this->deploymentScripts()->create([
                    'name' => 'pre-flight',
                    'content' => '',
                    'configs' => [
                        'restart_workers' => $this->deploymentScript?->shouldRestartWorkers() ?? false,
                    ],
                ]);
                $created = true;
            }
        }

        if (! $this->deploymentScript) {
            $this->deploymentScripts()->create([
                'name' => 'default',
                'content' => '',
            ]);
            $created = true;
        }

        if ($created) {
            $this->refresh();
        }
    }

    public function modernDeploymentEnabled(): bool
    {
        return (bool) ($this->type_data['modern_deployment'] ?? false);
    }

    /**
     * Resolve the deployment script that drives a deploy of the given mode.
     * Modern deploys use the pre-flight script; classic deploys use the default script.
     */
    public function deploymentScriptFor(bool $modern): ?DeploymentScript
    {
        return $modern ? $this->preFlightScript : $this->deploymentScript;
    }

    public function activeDeploymentScript(): ?DeploymentScript
    {
        return $this->deploymentScriptFor($this->modernDeploymentEnabled());
    }

    public function statsEnabled(): bool
    {
        return ! (bool) ($this->type_data['stats_disabled'] ?? false);
    }

    /**
     * @return HasMany<Worker, covariant $this>
     */
    public function workers(): HasMany
    {
        return $this->hasMany(Worker::class);
    }

    /**
     * @return HasMany<CronJob, covariant $this>
     */
    public function cronJobs(): HasMany
    {
        return $this->hasMany(CronJob::class);
    }

    /**
     * @return HasMany<Ssl, covariant $this>
     */
    public function ssls(): HasMany
    {
        return $this->hasMany(Ssl::class);
    }

    /**
     * @return HasMany<HostedDomain, covariant $this>
     */
    public function hostedDomains(): HasMany
    {
        return $this->hasMany(HostedDomain::class);
    }

    /**
     * @return HasOne<HostedDomain, covariant $this>
     */
    public function primaryHostedDomain(): HasOne
    {
        return $this->hasOne(HostedDomain::class)->where('type', HostedDomainType::PRIMARY);
    }

    /**
     * @return BelongsTo<SourceControl, covariant $this>
     */
    public function sourceControl(): BelongsTo
    {
        return $this->belongsTo(SourceControl::class)->withTrashed();
    }

    public function getFullRepositoryUrl(): ?string
    {
        return $this->sourceControl?->provider()?->fullRepoUrl($this->repository, $this->getSshKeyName());
    }

    public function getAliasesString(): string
    {
        if (count($this->aliases) > 0) {
            return implode(' ', $this->aliases);
        }

        return '';
    }

    public function type(): SiteType
    {
        $type = match ($this->type) {
            'mise_bun' => BunSite::id(),
            'mise_nodejs' => NodeSite::id(),
            default => $this->type,
        };

        $handlerClass = config('site.types.'.$type.'.handler');
        if (! class_exists($handlerClass)) {
            throw new RuntimeException("Site type handler class {$handlerClass} does not exist.");
        }

        /** @var SiteType $handler */
        $handler = new $handlerClass($this);

        return $handler;
    }

    public function php(): ?Service
    {
        if ($this->php_version) {
            return $this->server->php($this->php_version);
        }

        return null;
    }

    public function getUrl(): string
    {
        if ($this->ssl_enabled) {
            return 'https://'.$this->domain;
        }

        return 'http://'.$this->domain;
    }

    public function getWebDirectoryPath(): string
    {
        if ($this->web_directory) {
            return $this->path.'/'.$this->web_directory;
        }

        return $this->path;
    }

    /**
     * @throws SourceControlIsNotConnected
     */
    public function enableAutoDeployment(): void
    {
        if ($this->gitHook) {
            return;
        }

        if (! $this->sourceControl?->getRepo($this->repository)) {
            throw new SourceControlIsNotConnected($this->source_control);
        }

        $gitHook = new GitHook([
            'site_id' => $this->id,
            'source_control_id' => $this->source_control_id,
            'secret' => Str::uuid()->toString(),
            'actions' => ['deploy'],
            'events' => ['push'],
        ]);
        $gitHook->save();
        $gitHook->deployHook();
    }

    /**
     * @throws SourceControlIsNotConnected
     */
    public function disableAutoDeployment(): void
    {
        if (! $this->sourceControl?->getRepo($this->repository)) {
            throw new SourceControlIsNotConnected($this->source_control);
        }

        $this->gitHook?->destroyHook();
    }

    public function isAutoDeployment(): bool
    {
        return (bool) $this->gitHook;
    }

    public function getSshKeyName(): string
    {
        if ($this->getRawOriginal('ssh_key')) {
            return 'site_'.$this->id;
        }

        return $this->isolated_user_id
            ? 'iuser_'.$this->isolated_user_id
            : 'site_'.$this->id;
    }

    public function getEnv(): string
    {
        try {
            $envPath = $this->type_data['env_path'] ?? $this->path.'/.env';

            return $this->server->os()->readFile($envPath);
        } catch (SSHError) {
            return '';
        }
    }

    /**
     * @return array<string, string>
     */
    public function environmentVariables(?Deployment $deployment = null): array
    {
        $variables = [
            'SITE_PATH' => $this->path,
            'DOMAIN' => $this->domain,
            'BRANCH' => $this->branch ?? '',
            'REPOSITORY' => $this->repository ?? '',
            'COMMIT_ID' => $deployment->commit_id ?? '',
            'PHP_VERSION' => $this->php_version,
            'PHP_PATH' => '/usr/bin/php'.$this->php_version,
        ];

        if ($this->sourceControl?->isGithubApp()) {
            /** @var GithubApp $provider */
            $provider = $this->sourceControl->provider();
            $variables['GIT_HTTP_TOKEN'] = $provider->installationAccessToken();
        }

        return $variables;
    }

    /**
     * @return array<string, string>
     */
    public function environmentAliases(): array
    {
        return [
            'php' => '/usr/bin/php'.$this->php_version,
        ];
    }

    public function isIsolated(): bool
    {
        if ($this->isolated_user_id !== null) {
            return true;
        }

        $column = $this->getRawOriginal('user');

        return is_string($column) && $column !== '' && $column !== $this->server->getSshUser();
    }

    public function userSharedWithSiblings(): bool
    {
        return $this->siblingsSharingUser()->exists();
    }

    /**
     * @return Builder<Site>
     */
    public function siblingsSharingUser(bool $includeSelf = false): Builder
    {
        if (! $this->isolated_user_id) {
            return Site::query()->whereRaw('1 = 0');
        }

        $query = Site::query()->where('isolated_user_id', $this->isolated_user_id);

        if (! $includeSelf) {
            $query->where('id', '!=', $this->id);
        }

        return $query;
    }

    /**
     * @return array<int, string>
     */
    public function availableToolingCommands(): array
    {
        $commands = [];

        if ($this->php_version) {
            $commands[] = 'php';
        }

        foreach (ToolingRegistry::all() as $id => $tool) {
            if ($this->isolatedUser?->toolingVersion($id) !== null) {
                $commands = array_merge($commands, $tool::commands());
            }
        }

        return array_values(array_unique($commands));
    }

    /**
     * @return array<string, string>
     */
    public function requiredToolingMap(): array
    {
        $required = [];

        foreach ($this->siblingsSharingUser(includeSelf: true)->get() as $site) {
            $type = $site->type();
            if (! $type instanceof AbstractSiteType) {
                continue;
            }

            $typeId = $type::id();
            $label = config('site.types.'.$typeId.'.label') ?? $typeId;

            foreach ($type::requiredTooling() as $toolId) {
                $required[$toolId] = $label;
            }
        }

        return $required;
    }

    public function fpmPoolSharedWithSiblings(?string $phpVersion = null): bool
    {
        if (! $this->isolated_user_id) {
            return false;
        }

        return Site::query()
            ->where('isolated_user_id', $this->isolated_user_id)
            ->where('php_version', $phpVersion ?? $this->php_version)
            ->where('id', '!=', $this->id)
            ->exists();
    }

    public function webserver(): Webserver
    {
        /** @var Service $webserver */
        $webserver = $this->server->webserver();

        /** @var Webserver $handler */
        $handler = $webserver->handler();

        return $handler;
    }

    /**
     * @return HasMany<LoadBalancerServer, covariant $this>
     */
    public function loadBalancerServers(): HasMany
    {
        return $this->hasMany(LoadBalancerServer::class, 'load_balancer_id');
    }

    /**
     * @return array<string>
     */
    public function getSshUsers(): array
    {
        $users = [
            'root',
            $this->server->getSshUser(),
        ];

        if ($this->isIsolated()) {
            $users[] = $this->user;
        }

        return $users;
    }

    /**
     * @return HasMany<Redirect, covariant $this>
     */
    public function redirects(): HasMany
    {
        return $this->hasMany(Redirect::class);
    }

    /**
     * @return HasMany<Redirect, covariant $this>
     */
    public function activeRedirects(): HasMany
    {
        return $this->redirects()->whereIn('status', [RedirectStatus::CREATING, RedirectStatus::READY]);
    }

    /**
     * @return array<string, mixed>
     */
    public function features(): array
    {
        $features = config('site.types.'.$this->type.'.features', []);
        foreach ($features as $featureKey => $feature) {
            foreach ($feature['actions'] ?? [] as $actionKey => $action) {
                $handlerClass = $action['handler'] ?? null;
                if ($handlerClass && class_exists($handlerClass)) {
                    /** @var ActionInterface $handler */
                    $handler = new $handlerClass($this);
                    $action['active'] = $handler->active();
                    if (! isset($action['form']) || empty($action['form'])) {
                        $action['form'] = $handler->form()?->toArray() ?? [];
                    }
                }
                $features[$featureKey]['actions'][$actionKey] = $action;
            }
        }

        return $features;
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, config('site.types.'.$this->type.'.features', []));
    }

    public function createDefaultDeploymentScript(): void
    {
        if ($this->deploymentScript) {
            return;
        }

        try {
            $script = $this->type()->defaultDeploymentScript();
        } catch (\Throwable $e) {
            Log::error('Failed to render default deploy script for site '.$this->id, [
                'type' => $this->type,
                'error' => $e->getMessage(),
            ]);
            $script = '';
        }

        $deploymentScript = new DeploymentScript([
            'site_id' => $this->id,
            'name' => 'default',
            'content' => $script,
            'configs' => [
                'restart_workers' => true,
            ],
        ]);
        $deploymentScript->save();
        $this->refresh();
    }

    public function basePath(): string
    {
        return preg_replace('#/current$#', '', $this->path);
    }

    public function htpasswdPath(): string
    {
        return '/etc/nginx/auth/site-'.$this->id.'.htpasswd';
    }

    public function getDeployKeyName(): string
    {
        return $this->domain.'-key-'.$this->id;
    }
}
