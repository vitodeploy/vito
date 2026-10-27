<?php

namespace App\Models;

use App\ComponentCapabilities\AbstractComponentCapability;
use App\Enums\ComponentStatus;
use Database\Factories\ComponentCapabilityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

/**
 * @property int $component_id
 * @property string $capability
 * @property ?array<string, mixed> $config
 * @property ComponentStatus $status
 * @property Component $component
 */
class ComponentCapability extends AbstractModel
{
    /** @use HasFactory<ComponentCapabilityFactory> */
    use HasFactory;

    protected $fillable = [
        'component_id',
        'capability',
        'config',
    ];

    protected $casts = [
        'component_id' => 'integer',
        'config' => 'json',
        'status' => ComponentStatus::class,
    ];

    public function handler(): AbstractComponentCapability
    {
        $handler = config('component.capabilities', [])[$this->capability]['handler'] ?? null;

        if (! is_string($handler) || ! is_subclass_of($handler, AbstractComponentCapability::class)) {
            throw new InvalidArgumentException("Component capability for {$this->capability} is not defined.");
        }

        return new $handler($this);
    }

    /**
     * @return BelongsTo<Component, covariant $this>
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }
}
