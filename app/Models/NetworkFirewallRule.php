<?php

namespace App\Models;

use App\Enums\FirewallRuleStatus;
use Database\Factories\NetworkFirewallRuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $network_id
 * @property string $name
 * @property string $type
 * @property ?string $protocol
 * @property ?string $port
 * @property int $position
 * @property FirewallRuleStatus $status
 * @property Network $network
 */
class NetworkFirewallRule extends AbstractModel
{
    /** @use HasFactory<NetworkFirewallRuleFactory> */
    use HasFactory;

    protected $fillable = [
        'network_id',
        'name',
        'type',
        'protocol',
        'port',
        'position',
        'status',
    ];

    protected $casts = [
        'network_id' => 'integer',
        'position' => 'integer',
        'status' => FirewallRuleStatus::class,
    ];

    /**
     * @return BelongsTo<Network, covariant $this>
     */
    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class);
    }
}
