<?php

namespace App\Models;

use App\Traits\HasExclusiveOwner;
use Database\Factories\DeploymentScriptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property ?int $site_id
 * @property ?int $component_id
 * @property ?int $environment_id
 * @property string $name
 * @property string $content
 * @property ?Environment $environment
 * @property ?Component $component
 * @property ?Site $site
 */
class DeploymentScript extends AbstractModel
{
    use HasExclusiveOwner;

    /** @use HasFactory<DeploymentScriptFactory> */
    use HasFactory;

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function ($deploymentScript): void {
            $deploymentScript->content = str_replace("\r\n", "\n", $deploymentScript->content);
        });
    }

    protected $fillable = [
        'site_id',
        'component_id',
        'environment_id',
        'name',
        'content',
        'configs',
    ];

    protected $casts = [
        'site_id' => 'integer',
        'component_id' => 'integer',
        'environment_id' => 'integer',
        'configs' => 'array',
    ];

    /**
     * @return list<string>
     */
    public function ownerColumns(): array
    {
        return ['site_id', 'environment_id', 'component_id'];
    }

    public function requiresOwner(): bool
    {
        return true;
    }

    /**
     * @return BelongsTo<Environment, covariant $this>
     */
    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
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

    public function shouldRestartWorkers(): bool
    {
        return (bool) ($this->configs['restart_workers'] ?? false);
    }
}
