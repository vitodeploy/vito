<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * @property int $component_id
 * @property ?int $target_component_id
 * @property ?string $ip
 * @property int $port
 * @property ?int $weight
 * @property bool $backup
 * @property ?int $firewall_rule_id
 * @property Component $component
 * @property ?Component $targetComponent
 * @property ?FirewallRule $firewallRule
 */
class ComponentBackend extends AbstractModel
{
    protected $fillable = ['target_component_id', 'ip', 'port', 'weight', 'backup'];

    protected $casts = [
        'component_id' => 'integer',
        'target_component_id' => 'integer',
        'firewall_rule_id' => 'integer',
        'port' => 'integer',
        'weight' => 'integer',
        'backup' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (ComponentBackend $backend): void {
            if (($backend->target_component_id === null) === ($backend->ip === null)) {
                throw new LogicException('A backend must have exactly one component or IP target.');
            }
        });
    }

    /** @return BelongsTo<Component, covariant $this> */
    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    /** @return BelongsTo<Component, covariant $this> */
    public function targetComponent(): BelongsTo
    {
        return $this->belongsTo(Component::class, 'target_component_id');
    }

    /** @return BelongsTo<FirewallRule, covariant $this> */
    public function firewallRule(): BelongsTo
    {
        return $this->belongsTo(FirewallRule::class);
    }
}
