<?php

namespace App\Models;

use App\Enums\DeploymentStatus;
use Database\Factories\DeploymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $site_id
 * @property int $deployment_script_id
 * @property int $log_id
 * @property string $commit_id
 * @property string $commit_id_short
 * @property array<string, mixed> $commit_data
 * @property string $status
 * @property ?string $release
 * @property Site $site
 * @property DeploymentScript $deploymentScript
 * @property ?ServerLog $log
 */
class Deployment extends AbstractModel
{
    /** @use HasFactory<DeploymentFactory> */
    use HasFactory;

    protected $fillable = [
        'site_id',
        'deployment_script_id',
        'log_id',
        'commit_id',
        'commit_data',
        'status',
        'release',
    ];

    protected $casts = [
        'site_id' => 'integer',
        'deployment_script_id' => 'integer',
        'log_id' => 'integer',
        'commit_data' => 'json',
    ];

    /**
     * @var array<string, string>
     */
    public static array $statusColors = [
        DeploymentStatus::DEPLOYING => 'warning',
        DeploymentStatus::FINISHED => 'success',
        DeploymentStatus::FAILED => 'danger',
    ];

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

    public function path(): string
    {
        return $this->site->basePath().($this->release ? '/releases/'.$this->release : '');
    }
}
