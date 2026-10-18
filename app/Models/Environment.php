<?php

namespace App\Models;

use App\Enums\EnvironmentStatus;
use Database\Factories\EnvironmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $application_id
 * @property string $name
 * @property string $slug
 * @property string $branch
 * @property ?string $runtime_version
 * @property ?int $primary_component_id
 * @property bool $primary_component_initialized
 * @property ?string $last_error
 * @property EnvironmentStatus $status
 * @property Application $application
 * @property ?Component $primaryComponent
 * @property Collection<int, Component> $components
 * @property Collection<int, DeploymentScript> $deploymentScripts
 * @property Collection<int, EnvironmentVariable> $variables
 * @property bool $needs_redeploy
 */
class Environment extends AbstractModel
{
    /** @use HasFactory<EnvironmentFactory> */
    use HasFactory;

    protected $fillable = [
        'application_id',
        'name',
        'branch',
        'runtime_version',
    ];

    protected $casts = [
        'application_id' => 'integer',
        'primary_component_id' => 'integer',
        'primary_component_initialized' => 'boolean',
        'status' => EnvironmentStatus::class,
    ];

    /**
     * @return BelongsTo<Application, covariant $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return HasMany<Component, covariant $this>
     */
    public function components(): HasMany
    {
        return $this->hasMany(Component::class);
    }

    /**
     * @return BelongsTo<Component, covariant $this>
     */
    public function primaryComponent(): BelongsTo
    {
        return $this->belongsTo(Component::class, 'primary_component_id');
    }

    /**
     * @return HasMany<EnvironmentVariable, covariant $this>
     */
    public function variables(): HasMany
    {
        return $this->hasMany(EnvironmentVariable::class);
    }

    /**
     * @return HasMany<DeploymentScript, covariant $this>
     */
    public function deploymentScripts(): HasMany
    {
        return $this->hasMany(DeploymentScript::class);
    }
}
