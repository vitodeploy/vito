<?php

namespace App\Models;

use App\Enums\RedirectStatus;
use App\Enums\SiteStatus;
use App\Enums\SslStatus;
use App\Exceptions\FailedToDestroyGitHook;
use App\Exceptions\SourceControlIsNotConnected;
use App\Exceptions\SSHError;
use App\Services\PHP\PHP;
use App\Services\Webserver\Webserver;
use App\SiteFeatures\ActionInterface;
use App\SiteTypes\SiteType;
use App\Traits\HasProjectThroughServer;
use Database\Factories\SiteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * @property int $server_id
 * @property string $type
 * @property array<string, string> $type_data
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
 * @property string $status
 * @property int $port
 * @property int $progress
 * @property string $user
 * @property bool $force_ssl
 * @property Server $server
 * @property Collection<int, ServerLog> $logs
 * @property Collection<int, Deployment> $deployments
 * @property Collection<int, Command> $commands
 * @property ?GitHook $gitHook
 * @property ?DeploymentScript $deploymentScript
 * @property Collection<int, Worker> $workers
 * @property Collection<int, Ssl> $ssls
 * @property ?Ssl $activeSsl
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
        'type',
        'type_data',
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
    ];

    protected $casts = [
        'server_id' => 'integer',
        'type_data' => 'json',
        'port' => 'integer',
        'progress' => 'integer',
        'aliases' => 'array',
        'source_control_id' => 'integer',
        'force_ssl' => 'boolean',
    ];

    /**
     * @var array<string, string>
     */
    public static array $statusColors = [
        SiteStatus::READY => 'success',
        SiteStatus::INSTALLING => 'warning',
        SiteStatus::INSTALLATION_FAILED => 'danger',
        SiteStatus::DELETING => 'danger',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::deleting(function (Site $site): void {
            $site->workers()->each(function ($worker): void {
                /** @var Worker $worker */
                $worker->delete();
            });
            $site->ssls()->delete();
            $site->deployments()->delete();
            $site->deploymentScript()->delete();
        });

        static::created(function (Site $site): void {
            $site->deploymentScript()->create([
                'name' => 'default',
                'content' => '',
            ]);
        });
    }

    public function isReady(): bool
    {
        return $this->status === SiteStatus::READY;
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
     * @return BelongsTo<Server, covariant $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
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
     * @return HasOne<DeploymentScript, covariant $this>
     */
    public function deploymentScript(): HasOne
    {
        return $this->hasOne(DeploymentScript::class);
    }

    /**
     * @return HasMany<Worker, covariant $this>
     */
    public function workers(): HasMany
    {
        return $this->hasMany(Worker::class);
    }

    /**
     * @return HasMany<Ssl, covariant $this>
     */
    public function ssls(): HasMany
    {
        return $this->hasMany(Ssl::class);
    }

    /**
     * @return MorphToMany<Tag, covariant $this>
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
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
        $handlerClass = config('site.types.'.$this->type.'.handler');
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

    /**
     * @throws SSHError
     */
    public function changePHPVersion(string $version): void
    {
        $webserver = $this->webserver();
        $webserver->changePHPVersion($this, $version);

        if ($this->isIsolated()) {
            /** @var Service $php */
            $php = $this->server->php();
            /** @var PHP $phpHandler */
            $phpHandler = $php->handler();
            $phpHandler->removeFpmPool($this->user, $this->php_version, $this->id);
            $phpHandler->createFpmPool($this->user, $version);
        }

        $this->php_version = $version;
        $this->save();
    }

    /**
     * @return HasOne<Ssl, covariant $this>
     */
    public function activeSsl(): HasOne
    {
        return $this->hasOne(Ssl::class)
            ->where('expires_at', '>=', now())
            ->where('status', SslStatus::CREATED)
            ->where('is_active', true)
            ->orderByDesc('id');
    }

    public function getUrl(): string
    {
        if ($this->activeSsl) {
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
     * @throws FailedToDestroyGitHook
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
        return str('site_'.$this->id)->toString();
    }

    public function getEnv(): string
    {
        try {
            return $this->server->os()->readFile($this->path.'/.env');
        } catch (SSHError) {
            return '';
        }
    }

    /**
     * @return array<string, string>
     */
    public function environmentVariables(?Deployment $deployment = null): array
    {
        return [
            'SITE_PATH' => $this->path,
            'DOMAIN' => $this->domain,
            'BRANCH' => $this->branch ?? '',
            'REPOSITORY' => $this->repository ?? '',
            'COMMIT_ID' => $deployment->commit_id ?? '',
            'PHP_VERSION' => $this->php_version,
            'PHP_PATH' => '/usr/bin/php'.$this->php_version,
        ];
    }

    public function isIsolated(): bool
    {
        return $this->user != $this->server->getSshUser();
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
                }
                $features[$featureKey]['actions'][$actionKey] = $action;
            }
        }

        return $features;
    }
}
