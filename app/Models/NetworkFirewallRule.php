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
 * @property ?string $protocol
 * @property ?string $port
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
}
