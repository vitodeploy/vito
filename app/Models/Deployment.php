<?php

namespace App\Models;

use App\Enums\DeploymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deployment extends AbstractModel
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'deployment_script_id',
        'log_id',
        'commit_id',
        'commit_data',
        'status',
    ];

    protected $casts = [
        'site_id' => 'integer',
        'deployment_script_id' => 'integer',
        'log_id' => 'integer',
        'commit_data' => 'json',
    ];

    public static array $statusColors = [
        DeploymentStatus::DEPLOYING => 'warning',
        DeploymentStatus::FINISHED => 'success',
        DeploymentStatus::FAILED => 'danger',
    ];

    /**
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * @return BelongsTo<DeploymentScript, $this>
     */
    public function deploymentScript(): BelongsTo
    {
        return $this->belongsTo(DeploymentScript::class);
    }

    /**
     * @return BelongsTo<ServerLog, $this>
     */
    public function log(): BelongsTo
    {
        return $this->belongsTo(ServerLog::class, 'log_id');
    }
}
