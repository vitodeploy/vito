<?php

namespace App\Models;

use App\Enums\FirewallRuleStatus;
use Database\Factories\NetworkFirewallRuleFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $network_id
 * @property string $name
 * @property ?string $protocol
 * @property ?string $port
 * @property FirewallRuleStatus $status
 * @property Network $network
 * @property Collection<int, ServerNetworkRule> $serverRules
 */
class NetworkFirewallRule extends AbstractModel
{
    /** @use HasFactory<NetworkFirewallRuleFactory> */
    use HasFactory;

    protected $fillable = [
        'network_id',
        'name',
        'protocol',
        'port',
        'status',
    ];

    protected $casts = [
        'network_id' => 'integer',
        'status' => FirewallRuleStatus::class,
    ];

    /**
     * @return BelongsTo<Network, covariant $this>
     */
    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class);
    }

    /**
     * @return HasMany<ServerNetworkRule, covariant $this>
     */
    public function serverRules(): HasMany
    {
        return $this->hasMany(ServerNetworkRule::class);
    }
}
