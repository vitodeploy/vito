<?php

namespace App\Models;

use App\Enums\DeploymentStatus;
use App\Enums\DeploymentStage;
use App\Traits\HasExclusiveOwner;
use Database\Factories\DeploymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property ?int $environment_deployment_id
 * @property ?EnvironmentDeployment $environmentDeployment
 * @property ?DeploymentStage $current_stage
 * @property ?int $previous_deployment_id
 * @property ?Deployment $previousDeployment
 * @property bool $release_started
 * @property ?int $site_id
 * @property ?int $component_id
 * @property ?int $deployment_script_id
 * @property int $log_id
 * @property string $commit_id
 * @property string $commit_id_short
 * @property array<string, mixed> $commit_data
 * @property DeploymentStatus $status
 * @property ?string $release
 * @property bool $active
 * @property ?Component $component
 * @property ?Site $site
 * @property ?DeploymentScript $deploymentScript
 * @property ?ServerLog $log
 */
class Deployment extends AbstractModel
{
    use HasExclusiveOwner;

    /** @use HasFactory<DeploymentFactory> */
    use HasFactory;

    protected $fillable = [
        'site_id',
        'component_id',
        'deployment_script_id',
        'log_id',
        'commit_id',
        'commit_data',
        'status',
        'release',
        'active',
    ];

    protected $casts = [
        'site_id' => 'integer',
        'component_id' => 'integer',
        'deployment_script_id' => 'integer',
        'log_id' => 'integer',
        'commit_data' => 'json',
        'active' => 'boolean',
        'status' => DeploymentStatus::class,
        'environment_deployment_id' => 'integer',
        'current_stage' => DeploymentStage::class,
        'previous_deployment_id' => 'integer',
        'release_started' => 'boolean',
    ];

    /**
     * @return list<string>
     */
    public function ownerColumns(): array
    {
        return ['site_id', 'component_id'];
    }

    public function requiresOwner(): bool
    {
        return true;
    }

    protected static function booted(): void
    {
        static::created(function (Deployment $deployment): void {
            $site = $deployment->site;
            if (! $site) {
                return;
            }

            $keep = $site->type_data['modern_deployment_history'] ?? 10;
            if ($site->deployments()->whereNotNull('release')->count() > $keep) {
                /** @var ?Deployment $lastDeploymentToKeep */
                $lastDeploymentToKeep = $site->deployments()->whereNotNull('release')->orderByDesc('id')->skip($keep)->first();
                if ($lastDeploymentToKeep) {
                    $deployments = $site->deployments()->whereNotNull('release')
                        ->where('id', '<=', $lastDeploymentToKeep->id)
                        ->get();
                    /** @var Deployment $deployment */
                    foreach ($deployments as $deployment) {
                        $deployment->remove(true);
                    }
                }
            }
        });
    }

    /**
     * @return BelongsTo<Component, covariant $this>
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    /**
     * @return BelongsTo<Site, covariant $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * @return BelongsTo<DeploymentScript, covariant $this>
     */
    public function deploymentScript(): BelongsTo
    {
        return $this->belongsTo(DeploymentScript::class);
    }

    /**
     * @return BelongsTo<ServerLog, covariant $this>
     */
    public function log(): BelongsTo
    {
        return $this->belongsTo(ServerLog::class, 'log_id');
    }

    /** @return BelongsTo<EnvironmentDeployment, covariant $this> */
    public function environmentDeployment(): BelongsTo
    {
        return $this->belongsTo(EnvironmentDeployment::class);
    }

    /** @return BelongsTo<Deployment, covariant $this> */
    public function previousDeployment(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_deployment_id');
    }

    public function path(): string
    {
        return ($this->component ?? $this->site)->basePath().($this->release ? '/releases/'.$this->release : '');
    }

    public function remove(bool $onlyRelease = false): void
    {
        if ($this->component && ($this->active || ! $this->environmentDeployment?->status->isTerminal())) {
            return;
        }
        if ($this->release) {
            $owner = $this->component ?? $this->site;
            $command = 'rm -rf -- '.escapeshellarg($this->path());
            if ($this->component) {
                $command .= ' && rm -f -- '.escapeshellarg($this->component->sharedPath().'/.env-'.$this->id.'.prepared');
            }
            $owner->server->ssh($owner->user)->exec($command);
            $this->release = null;
        }

        if ($onlyRelease) {
            $this->save();

            return;
        }

        $this->delete();
    }

    public function pruneOldReleases(): void
    {
        if (! $this->component || ! $this->environmentDeployment?->status->isTerminal()) {
            return;
        }
        $releases = $this->component->deployments()->with('environmentDeployment')->whereNotNull('release')->orderByDesc('id')->get();
        $protected = $releases->filter(fn (Deployment $deployment): bool => ! $deployment->environmentDeployment?->status->isTerminal())->pluck('previous_deployment_id');
        foreach ($releases->skip($this->component->environment->keep_releases) as $deployment) {
            if (! $protected->contains($deployment->id)) {
                $deployment->remove(true);
            }
        }
    }

    public function activate(): void
    {
        ($this->component ?? $this->site)->deployments()->update(['active' => false]);
        $this->active = true;
        $this->save();
    }
}
