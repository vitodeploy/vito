<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\RuntimePresets\AbstractRuntimePreset;
use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * @property int $project_id
 * @property ?int $source_control_id
 * @property string $name
 * @property string $repository
 * @property string $runtime
 * @property ApplicationStatus $status
 * @property Project $project
 * @property ?SourceControl $sourceControl
 * @property Collection<int, Environment> $environments
 */
class Application extends AbstractModel
{
    /** @use HasFactory<ApplicationFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'source_control_id',
        'name',
        'repository',
        'runtime',
    ];

    protected $casts = [
        'project_id' => 'integer',
        'source_control_id' => 'integer',
        'status' => ApplicationStatus::class,
    ];

    public function preset(): AbstractRuntimePreset
    {
        $handler = config('component.runtimes', [])[$this->runtime]['handler'] ?? null;

        if (! is_string($handler) || ! is_subclass_of($handler, AbstractRuntimePreset::class)) {
            throw new InvalidArgumentException("Runtime preset for {$this->runtime} is not defined.");
        }

        return new $handler;
    }

    /**
     * @return BelongsTo<Project, covariant $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<SourceControl, covariant $this>
     */
    public function sourceControl(): BelongsTo
    {
        return $this->belongsTo(SourceControl::class);
    }

    /**
     * @return HasMany<Environment, covariant $this>
     */
    public function environments(): HasMany
    {
        return $this->hasMany(Environment::class);
    }
}
