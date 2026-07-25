<?php

namespace App\Models;

use App\Enums\NetworkServerStatus;
use Database\Factories\NetworkServerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $network_id
 * @property int $server_id
 * @property ?int $server_ip_address_id
 * @property ?string $ip
 * @property ?string $public_key
 * @property ?string $private_key
 * @property NetworkServerStatus $status
 * @property int $sync_attempts
 * @property Network $network
 * @property Server $server
 * @property ?ServerIpAddress $serverIpAddress
 */
class NetworkServer extends AbstractModel
{
    /** @use HasFactory<NetworkServerFactory> */
    use HasFactory;

    protected $fillable = [
        'network_id',
        'server_id',
        'server_ip_address_id',
        'ip',
        'public_key',
        'private_key',
        'status',
        'sync_attempts',
    ];

    protected $casts = [
        'network_id' => 'integer',
        'server_id' => 'integer',
        'server_ip_address_id' => 'integer',
        'sync_attempts' => 'integer',
        'status' => NetworkServerStatus::class,
        'private_key' => 'encrypted',
    ];

    protected $hidden = [
        'private_key',
    ];

    /**
     * @return BelongsTo<Network, covariant $this>
     */
    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class);
    }

    /**
     * @return BelongsTo<Server, covariant $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * @return BelongsTo<ServerIpAddress, covariant $this>
     */
    public function serverIpAddress(): BelongsTo
    {
        return $this->belongsTo(ServerIpAddress::class);
    }

    /**
     * @return HasMany<ServerNetworkRule, covariant $this>
     */
    public function rules(): HasMany
    {
        return $this->hasMany(ServerNetworkRule::class);
    }
}
