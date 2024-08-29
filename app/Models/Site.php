<?php

namespace App\Models;

use App\Exceptions\SourceControlIsNotConnected;
use App\Exceptions\SSHError;
use App\SiteTypes\SiteType;
use App\SSH\Services\Webserver\Webserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;

/**
 * @property int $server_id
 * @property string $type
 * @property array $type_data
 * @property string $domain
 * @property array $aliases
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
 * @property Server $server
 * @property ServerLog[] $logs
 * @property Deployment[] $deployments
 * @property ?GitHook $gitHook
 * @property DeploymentScript $deploymentScript
 * @property Queue[] $queues
 * @property Ssl[] $ssls
 * @property ?Ssl $activeSsl
 * @property string $ssh_key_name
 */
class Site extends AbstractModel
{
    use HasFactory;

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
    ];

    protected $casts = [
        'server_id' => 'integer',
        'type_data' => 'json',
        'port' => 'integer',
        'progress' => 'integer',
        'aliases' => 'array',
        'source_control_id' => 'integer',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::deleting(function (Site $site) {
            $site->queues()->delete();
            $site->ssls()->delete();
            $site->deployments()->delete();
            $site->deploymentScript()->delete();
        });

        static::created(function (Site $site) {
            $site->deploymentScript()->create([
                'name' => 'default',
                'content' => '',
            ]);
        });
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ServerLog::class);
    }

    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
    }

    public function gitHook(): HasOne
    {
        return $this->hasOne(GitHook::class);
    }

    public function deploymentScript(): HasOne
    {
        return $this->hasOne(DeploymentScript::class);
    }

    public function queues(): HasMany
    {
        return $this->hasMany(Queue::class);
    }

    public function ssls(): HasMany
    {
        return $this->hasMany(Ssl::class);
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    /**
     * @throws SourceControlIsNotConnected
     */
    public function sourceControl(): SourceControl|HasOne|null|Model
    {
        $sourceControl = null;

        if (! $this->source_control && ! $this->source_control_id) {
            return null;
        }

        if ($this->source_control) {
            $sourceControl = SourceControl::query()->where('provider', $this->source_control)->first();
        }

        if ($this->source_control_id) {
            $sourceControl = SourceControl::query()->find($this->source_control_id);
        }

        if (! $sourceControl) {
            throw new SourceControlIsNotConnected($this->source_control);
        }

        return $sourceControl;
    }

    /**
     * @throws SourceControlIsNotConnected
     */
    public function getFullRepositoryUrl()
    {
        return $this->sourceControl()->provider()->fullRepoUrl($this->repository, $this->getSshKeyName());
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
        $typeClass = config('core.site_types_class.'.$this->type);

        return new $typeClass($this);
    }

    public function php(): ?Service
    {
        if ($this->php_version) {
            return $this->server->php($this->php_version);
        }

        return null;
    }

    public function changePHPVersion($version): void
    {
        /** @var Webserver $handler */
        $handler = $this->server->webserver()->handler();
        $handler->changePHPVersion($this, $version);
        $this->php_version = $version;
        $this->save();
    }

    public function activeSsl(): HasOne
    {
        return $this->hasOne(Ssl::class)
            ->where('expires_at', '>=', now())
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

        if (! $this->sourceControl()?->getRepo($this->repository)) {
            throw new SourceControlIsNotConnected($this->source_control);
        }

        $gitHook = new GitHook([
            'site_id' => $this->id,
            'source_control_id' => $this->sourceControl()->id,
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
        if (! $this->sourceControl()?->getRepo($this->repository)) {
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

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->type()->supportedFeatures());
    }

    public function getEnv(): string
    {
        try {
            return $this->server->os()->readFile($this->path.'/.env');
        } catch (SSHError) {
            return '';
        }
    }

    public function hasSSL(): bool
    {
        return $this->ssls->isNotEmpty();
    }

    public function environmentVariables(?Deployment $deployment = null): array
    {
        return [
            'SITE_PATH' => $this->path,
            'DOMAIN' => $this->domain,
            'BRANCH' => $this->branch ?? '',
            'REPOSITORY' => $this->repository ?? '',
            'COMMIT_ID' => $deployment?->commit_id ?? '',
            'PHP_VERSION' => $this->php_version,
            'PHP_PATH' => '/usr/bin/php'.$this->php_version,
        ];
    }
}
