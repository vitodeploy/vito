<?php

namespace App\Models;

use App\Enums\NetworkAddressingPool;
use App\Enums\NetworkStatus;
use App\Enums\NetworkType;
use Database\Factories\NetworkFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $project_id
 * @property string $name
 * @property NetworkType $type
 * @property NetworkStatus $status
 * @property NetworkAddressingPool $addressing_pool
 * @property ?string $cidr
 * @property ?string $cidr_canonical
 * @property ?int $port
 * @property ?int $server_provider_id
 * @property ?string $external_id
 * @property ?string $region
 * @property ?Carbon $last_synced_at
 * @property Project $project
 * @property ?ServerProvider $serverProvider
 * @property Collection<int, NetworkServer> $servers
 * @property Collection<int, NetworkFirewallRule> $firewallRules
 * @property Collection<int, NetworkPeer> $peers
 * @property Collection<int, ServerNetworkRule> $serverRules
 * @property Collection<int, ServerLog> $serverLogs
 */
class Network extends AbstractModel
{
    /** @use HasFactory<NetworkFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'type',
        'status',
        'addressing_pool',
        'cidr',
        'cidr_canonical',
        'port',
        'region',
    ];

    protected $casts = [
        'project_id' => 'integer',
        'port' => 'integer',
        'server_provider_id' => 'integer',
        'last_synced_at' => 'datetime',
        'type' => NetworkType::class,
        'status' => NetworkStatus::class,
        'addressing_pool' => NetworkAddressingPool::class,
    ];

    /**
     * @return BelongsTo<Project, covariant $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<ServerProvider, covariant $this>
     */
    public function serverProvider(): BelongsTo
    {
        return $this->belongsTo(ServerProvider::class);
    }

    /**
     * @return HasMany<NetworkServer, covariant $this>
     */
    public function servers(): HasMany
    {
        return $this->hasMany(NetworkServer::class);
    }

    /**
     * @return HasMany<NetworkFirewallRule, covariant $this>
     */
    public function firewallRules(): HasMany
    {
        return $this->hasMany(NetworkFirewallRule::class);
    }

    /**
     * @return HasMany<ServerNetworkRule, covariant $this>
     */
    public function serverRules(): HasMany
    {
        return $this->hasMany(ServerNetworkRule::class);
    }

    /**
     * @return HasMany<NetworkPeer, covariant $this>
     */
    public function peers(): HasMany
    {
        return $this->hasMany(NetworkPeer::class);
    }

    /**
     * @return HasMany<ServerLog, covariant $this>
     */
    public function serverLogs(): HasMany
    {
        return $this->hasMany(ServerLog::class);
    }
}
