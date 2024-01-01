<?php

namespace App\Models;

use App\Contracts\SiteType;
use App\Enums\DeploymentStatus;
use App\Enums\SiteStatus;
use App\Enums\SslStatus;
use App\Events\Broadcast;
use App\Exceptions\SourceControlIsNotConnected;
use App\Jobs\Site\ChangePHPVersion;
use App\Jobs\Site\Deploy;
use App\Jobs\Site\DeployEnv;
use App\Jobs\Site\UpdateBranch;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * @property int $server_id
 * @property string $type
 * @property array $type_data
 * @property string $domain
 * @property array $aliases
 * @property string $web_directory
 * @property string $web_directory_path
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
 * @property bool $auto_deployment
 * @property string $url
 * @property Server $server
 * @property ServerLog[] $logs
 * @property Deployment[] $deployments
 * @property ?GitHook $gitHook
 * @property DeploymentScript $deploymentScript
 * @property Redirect[] $redirects
 * @property Queue[] $queues
 * @property Ssl[] $ssls
 * @property ?Ssl $activeSsl
 * @property string $full_repository_url
 * @property string $aliases_string
 * @property string $deployment_script_text
 * @property string $env
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
        'auto_deployment' => 'boolean',
        'aliases' => 'array',
        'source_control_id' => 'integer',
    ];

    protected $appends = [
        'url',
        'auto_deployment',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::deleting(function (Site $site) {
            $site->redirects()->delete();
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

    public function redirects(): HasMany
    {
        return $this->hasMany(Redirect::class);
    }

    public function queues(): HasMany
    {
        return $this->hasMany(Queue::class);
    }

    public function ssls(): HasMany
    {
        return $this->hasMany(Ssl::class);
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
    public function getFullRepositoryUrlAttribute()
    {
        return $this->sourceControl()->provider()->fullRepoUrl($this->repository, $this->ssh_key_name);
    }

    public function getAliasesStringAttribute(): string
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

    public function install(): void
    {
        $this->type()->install();
    }

    public function remove(): void
    {
        $this->update([
            'status' => SiteStatus::DELETING,
        ]);
        $this->type()->delete();
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
        dispatch(new ChangePHPVersion($this, $version))->onConnection('ssh');
    }

    public function getDeploymentScriptTextAttribute(): string
    {
        /* @var DeploymentScript $script */
        $script = $this->deploymentScript()->firstOrCreate([
            'site_id' => $this->id,
        ], [
            'site_id' => $this->id,
            'name' => 'default',
        ]);

        return $script->content;
    }

    /**
     * @throws SourceControlIsNotConnected
     */
    public function deploy(): Deployment
    {
        if ($this->sourceControl()) {
            $this->sourceControl()->getRepo($this->repository);
        }

        $deployment = new Deployment([
            'site_id' => $this->id,
            'deployment_script_id' => $this->deploymentScript->id,
            'status' => DeploymentStatus::DEPLOYING,
        ]);
        $lastCommit = $this->sourceControl()->provider()->getLastCommit($this->repository, $this->branch);
        if ($lastCommit) {
            $deployment->commit_id = $lastCommit['commit_id'];
            $deployment->commit_data = $lastCommit['commit_data'];
        }
        $deployment->save();

        dispatch(new Deploy($deployment, $this->path))->onConnection('ssh');

        return $deployment;
    }

    public function getEnvAttribute(): string
    {
        $typeData = $this->type_data;
        if (! isset($typeData['env'])) {
            $typeData['env'] = '';
            $this->type_data = $typeData;
            $this->save();
        }

        return $typeData['env'];
    }

    public function deployEnv(): void
    {
        dispatch(new DeployEnv($this))->onConnection('ssh');
    }

    public function activeSsl(): HasOne
    {
        return $this->hasOne(Ssl::class)
            ->where('expires_at', '>=', now())
            ->orderByDesc('id');
    }

    public function createFreeSsl(): void
    {
        $ssl = new Ssl([
            'site_id' => $this->id,
            'type' => 'letsencrypt',
            'expires_at' => now()->addMonths(3),
            'status' => SslStatus::CREATING,
        ]);
        $ssl->save();
        $ssl->deploy();
    }

    public function createCustomSsl(string $certificate, string $pk): void
    {
        $ssl = new Ssl([
            'site_id' => $this->id,
            'type' => 'custom',
            'certificate' => $certificate,
            'pk' => $pk,
            'expires_at' => '',
            'status' => SslStatus::CREATING,
        ]);
        $ssl->save();
        $ssl->deploy();
    }

    public function getUrlAttribute(): string
    {
        if ($this->activeSsl) {
            return 'https://'.$this->domain;
        }

        return 'http://'.$this->domain;
    }

    public function getWebDirectoryPathAttribute(): string
    {
        if ($this->web_directory) {
            return $this->path.'/'.$this->web_directory;
        }

        return $this->path;
    }

    /**
     * @throws SourceControlIsNotConnected
     * @throws Throwable
     */
    public function enableAutoDeployment(): void
    {
        if ($this->gitHook) {
            return;
        }

        if (! $this->sourceControl()) {
            throw new SourceControlIsNotConnected($this->source_control);
        }

        try {
            DB::beginTransaction();
            $gitHook = new GitHook([
                'site_id' => $this->id,
                'source_control_id' => $this->sourceControl()->id,
                'secret' => Str::uuid()->toString(),
                'actions' => ['deploy'],
                'events' => ['push'],
            ]);
            $gitHook->save();
            $gitHook->deployHook();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @throws Throwable
     */
    public function disableAutoDeployment(): void
    {
        $this->gitHook?->destroyHook();
    }

    public function getAutoDeploymentAttribute(): bool
    {
        return (bool) $this->gitHook;
    }

    public function updateBranch(string $branch): void
    {
        dispatch(new UpdateBranch($this, $branch))->onConnection('ssh');
    }

    public function getSshKeyNameAttribute(): string
    {
        return str('site_'.$this->id)->toString();
    }

    public function installationFinished(): void
    {
        $this->update([
            'status' => SiteStatus::READY,
            'progress' => 100,
        ]);
        event(
            new Broadcast('install-site-finished', [
                'site' => $this,
            ])
        );
        /** @todo notify */
    }

    /**
     * @throws Throwable
     */
    public function installationFailed(Throwable $e): void
    {
        $this->update([
            'status' => SiteStatus::INSTALLATION_FAILED,
        ]);
        event(
            new Broadcast('install-site-failed', [
                'site' => $this,
            ])
        );
        /** @todo notify */
        Log::error('install-site-error', [
            'error' => (string) $e,
        ]);

        throw $e;
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->type()->supportedFeatures());
    }

    public function isReady(): bool
    {
        return $this->status === SiteStatus::READY;
    }
}
