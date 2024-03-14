<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $site_id
 * @property int $deployment_script_id
 * @property int $log_id
 * @property string $commit_id
 * @property string $commit_id_short
 * @property array $commit_data
 * @property string $status
 * @property Site $site
 * @property DeploymentScript $deploymentScript
 * @property ServerLog $log
 */
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

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function deploymentScript(): BelongsTo
    {
        return $this->belongsTo(DeploymentScript::class);
    }

    public function log(): BelongsTo
    {
        return $this->belongsTo(ServerLog::class, 'log_id');
    }
}
