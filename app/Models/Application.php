<?php

namespace App\Models;

use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $project_id
 * @property ?int $source_control_id
 * @property string $name
 * @property string $repository
 * @property string $runtime
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
    ];

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
