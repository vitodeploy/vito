<?php

namespace App\Models;

use App\Enums\ComponentStatus;
use App\Enums\ComponentType;
use Database\Factories\ComponentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $environment_id
 * @property int $server_id
 * @property ?int $isolated_user_id
 * @property string $user
 * @property string $name
 * @property ComponentType $type
 * @property string $path
 * @property ComponentStatus $status
 * @property ?string $last_error
 * @property bool $needs_redeploy
 * @property Environment $environment
 * @property Server $server
 * @property ?IsolatedUser $isolatedUser
 * @property Collection<int, ComponentCapability> $capabilities
 * @property Collection<int, Worker> $workers
 * @property Collection<int, CronJob> $cronJobs
 * @property Collection<int, Deployment> $deployments
 * @property Collection<int, DeploymentScript> $deploymentScripts
 */
class Component extends AbstractModel
{
    /** @use HasFactory<ComponentFactory> */
    use HasFactory;

    protected $fillable = [
        'environment_id',
        'server_id',
        'isolated_user_id',
        'user',
        'name',
        'type',
    ];

    protected $casts = [
        'environment_id' => 'integer',
        'server_id' => 'integer',
        'isolated_user_id' => 'integer',
        'type' => ComponentType::class,
        'status' => ComponentStatus::class,
        'needs_redeploy' => 'boolean',
    ];

    /**
     * @return BelongsTo<Environment, covariant $this>
     */
    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
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

    /**
     * @return HasMany<ComponentCapability, covariant $this>
     */
    public function capabilities(): HasMany
    {
        return $this->hasMany(ComponentCapability::class);
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
     * @return HasMany<Deployment, covariant $this>
     */
    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
    }

    /**
     * @return HasMany<DeploymentScript, covariant $this>
     */
    public function deploymentScripts(): HasMany
    {
        return $this->hasMany(DeploymentScript::class);
    }

    public function basePath(): string
    {
        return $this->path;
    }

    public function currentPath(): string
    {
        return $this->basePath().'/current';
    }

    public function releasesPath(): string
    {
        return $this->basePath().'/releases';
    }

    public function sharedPath(): string
    {
        return $this->basePath().'/shared';
    }

    public function envPath(): string
    {
        return $this->sharedPath().'/.env';
    }

    public function hasCapability(string $capability): bool
    {
        return $this->capabilities->contains('capability', $capability);
    }

    public function project(): Project
    {
        return $this->environment->application->project;
    }
}
