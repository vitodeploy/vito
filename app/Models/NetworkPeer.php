<?php

namespace App\Models;

use App\Enums\NetworkPeerStatus;
use Database\Factories\NetworkPeerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $network_id
 * @property string $name
 * @property string $ip
 * @property string $public_key
 * @property ?string $private_key
 * @property bool $byo
 * @property NetworkPeerStatus $status
 * @property ?Carbon $last_handshake_at
 * @property int $sync_attempts
 * @property Network $network
 */
class NetworkPeer extends AbstractModel
{
    /** @use HasFactory<NetworkPeerFactory> */
    use HasFactory;

    protected $fillable = [
        'network_id',
        'name',
        'ip',
        'public_key',
        'private_key',
        'byo',
        'status',
        'last_handshake_at',
    ];

    protected $casts = [
        'network_id' => 'integer',
        'status' => NetworkPeerStatus::class,
        'private_key' => 'encrypted',
        'byo' => 'boolean',
        'last_handshake_at' => 'datetime',
        'sync_attempts' => 'integer',
    ];

    protected $hidden = [
        'private_key',
    ];

    /**
     * Whether Vito still holds this peer's private key. False once the key has been revealed
     * and concealed, and always false for peers that brought their own key.
     */
    public function hasPrivateKey(): bool
    {
        return ! $this->byo && $this->private_key !== null;
    }

    /**
     * @return BelongsTo<Network, covariant $this>
     */
    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class);
    }
}
