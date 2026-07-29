<?php

namespace App\Models;

use App\Enums\FirewallRuleStatus;
use App\Enums\ServerNetworkRuleKind;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @property int $id
 * @property int $server_id
 * @property int $network_id
 * @property int $network_server_id
 * @property ?int $network_firewall_rule_id
 * @property ServerNetworkRuleKind $kind
 * @property string $name
 * @property string $type
 * @property ?string $protocol
 * @property ?string $port
 * @property ?string $source
 * @property ?int $mask
 * @property FirewallRuleStatus $status
 * @property Server $server
 * @property Network $network
 * @property NetworkServer $networkServer
 * @property ?NetworkFirewallRule $networkFirewallRule
 */
class ServerNetworkRule extends AbstractModel
{
    protected $fillable = [
        'server_id',
        'network_id',
        'network_server_id',
        'network_firewall_rule_id',
        'kind',
        'name',
        'type',
        'protocol',
        'port',
        'source',
        'mask',
        'status',
    ];

    protected $casts = [
        'server_id' => 'integer',
        'network_id' => 'integer',
        'network_server_id' => 'integer',
        'network_firewall_rule_id' => 'integer',
        'mask' => 'integer',
        'kind' => ServerNetworkRuleKind::class,
        'status' => FirewallRuleStatus::class,
    ];

    /**
     * Network rows first (handshakes, then rules by network), stable by id.
     *
     * @param  Builder<ServerNetworkRule>|QueryBuilder|Relation<ServerNetworkRule, covariant AbstractModel, *>  $query
     */
    public static function applyOrder(Builder|QueryBuilder|Relation $query): void
    {
        $query
            ->orderByRaw('case when kind = ? then 0 else 1 end', [ServerNetworkRuleKind::HANDSHAKE->value])
            ->orderBy('network_id')
            ->orderBy('id');
    }

    /**
     * @param  Builder<ServerNetworkRule>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        self::applyOrder($query);
    }

    /**
     * @return BelongsTo<Server, covariant $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * @return BelongsTo<Network, covariant $this>
     */
    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class);
    }

    /**
     * @return BelongsTo<NetworkServer, covariant $this>
     */
    public function networkServer(): BelongsTo
    {
        return $this->belongsTo(NetworkServer::class);
    }

    /**
     * @return BelongsTo<NetworkFirewallRule, covariant $this>
     */
    public function networkFirewallRule(): BelongsTo
    {
        return $this->belongsTo(NetworkFirewallRule::class);
    }
}
