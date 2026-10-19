<?php

namespace App\Models;

use App\Enums\DeploymentStage;
use App\Enums\EnvironmentDeploymentStatus;
use Carbon\Carbon;
use Database\Factories\EnvironmentDeploymentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $environment_id
 * @property EnvironmentDeploymentStatus $status
 * @property ?DeploymentStage $current_stage
 * @property ?string $commit_id
 * @property ?array<string, mixed> $commit_data
 * @property string $release
 * @property string $branch
 * @property ?int $primary_component_id
 * @property ?int $triggered_by
 * @property ?Carbon $started_at
 * @property ?Carbon $finished_at
 * @property ?Carbon $stage_deadline_at
 * @property ?int $rollback_of_id
 * @property ?EnvironmentDeployment $rollbackOf
 * @property ?string $last_error
 * @property bool $is_join
 * @property bool $is_recovering
 * @property list<array{key: string, value: string, is_secret: bool}> $variables
 * @property ?list<int> $attachment_ids
 * @property int $variables_version
 * @property Environment $environment
 * @property ?User $triggeredBy
 * @property Collection<int, Deployment> $deployments
 */
class EnvironmentDeployment extends AbstractModel
{
    /** @use HasFactory<EnvironmentDeploymentFactory> */
    use HasFactory;

    protected $attributes = ['is_join' => false, 'is_recovering' => false];

    protected $hidden = ['variables'];

    protected $casts = [
        'environment_id' => 'integer',
        'status' => EnvironmentDeploymentStatus::class,
        'current_stage' => DeploymentStage::class,
        'commit_data' => 'json',
        'primary_component_id' => 'integer',
        'triggered_by' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'stage_deadline_at' => 'datetime',
        'rollback_of_id' => 'integer',
        'is_join' => 'boolean',
        'is_recovering' => 'boolean',
        'attachment_ids' => 'array',
        'variables' => 'encrypted:json',
        'variables_version' => 'integer',
    ];

    /** @param Builder<self> $query */
    public function scopeBlocking(Builder $query): void
    {
        $query->where(fn (Builder $query) => $query
            ->whereIn('status', [EnvironmentDeploymentStatus::PENDING, EnvironmentDeploymentStatus::RUNNING])
            ->orWhere(fn (Builder $query) => $query->where('status', EnvironmentDeploymentStatus::CANCELLED)->whereNotNull('stage_deadline_at')));
    }

    public function canCancel(): bool
    {
        return $this->status === EnvironmentDeploymentStatus::PENDING
            || ($this->status === EnvironmentDeploymentStatus::RUNNING && ! $this->is_recovering
                && ! in_array($this->current_stage, [DeploymentStage::RELEASE, DeploymentStage::POST_RELEASE], true));
    }

    public function canRollback(): bool
    {
        if ($this->is_join || $this->status !== EnvironmentDeploymentStatus::FINISHED) {
            return false;
        }
        $components = $this->environment->components()->where('type', \App\Enums\ComponentType::APP)->has('deployments');

        return $components->exists() && ! $components->where(fn (Builder $query) => $query->where('status', '!=', \App\Enums\ComponentStatus::READY)
            ->orWhereDoesntHave('deployments', fn (Builder $query) => $query->where('release', $this->release)->where('status', \App\Enums\DeploymentStatus::FINISHED)))->exists();
    }

    /** @return BelongsTo<self, covariant $this> */
    public function rollbackOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'rollback_of_id');
    }

    /** @return BelongsTo<Environment, covariant $this> */
    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    /** @return BelongsTo<User, covariant $this> */
    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    /** @return HasMany<Deployment, covariant $this> */
    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
    }
}
