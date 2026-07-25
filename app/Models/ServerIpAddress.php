<?php

namespace App\Models;

use App\Actions\Network\RemoveServerFromNetwork;
use App\Enums\IpAddressFamily;
use App\Enums\IpAddressStatus;
use App\Enums\IpAddressType;
use App\Enums\NetworkType;
use Database\Factories\ServerIpAddressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $server_id
 * @property string $ip
 * @property int $prefix_length
 * @property IpAddressFamily $family
 * @property ?string $interface
 * @property IpAddressType $type
 * @property IpAddressStatus $status
 * @property bool $is_managed
 * @property bool $is_primary
 * @property bool $is_dynamic
 * @property Server $server
 */
class ServerIpAddress extends AbstractModel
{
    /** @use HasFactory<ServerIpAddressFactory> */
    use HasFactory;

    protected $fillable = [
        'ip',
        'prefix_length',
        'family',
        'interface',
    ];

    protected $casts = [
        'server_id' => 'integer',
        'prefix_length' => 'integer',
        'is_managed' => 'boolean',
        'is_primary' => 'boolean',
        'is_dynamic' => 'boolean',
        'family' => IpAddressFamily::class,
        'type' => IpAddressType::class,
        'status' => IpAddressStatus::class,
    ];

    /** @var array<int, int> */
    public array $reapplyNetworkIds = [];

    /**
     * A CUSTOM membership is addressed solely by this row, and the foreign key is
     * nullOnDelete — losing the address leaves the membership with nothing to
     * announce, so it is removed rather than re-applied with an incomplete source.
     */
    protected static function booted(): void
    {
        static::deleting(function (ServerIpAddress $address): void {
            $address->reapplyNetworkIds = NetworkServer::query()
                ->where('server_ip_address_id', $address->id)
                ->whereHas('network', fn ($query) => $query->where('type', NetworkType::CUSTOM))
                ->pluck('network_id')
                ->unique()
                ->values()
                ->all();
        });

        static::deleted(function (ServerIpAddress $address): void {
            NetworkServer::query()
                ->whereIn('network_id', $address->reapplyNetworkIds)
                ->where('server_id', $address->server_id)
                ->get()
                ->each(fn (NetworkServer $member) => app(RemoveServerFromNetwork::class)->remove($member));
        });
    }

    /**
     * @return BelongsTo<Server, covariant $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public static function classifyType(string $ip): IpAddressType
    {
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return IpAddressType::UNKNOWN;
        }

        $isPublic = filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );

        return $isPublic !== false ? IpAddressType::PUBLIC : IpAddressType::PRIVATE;
    }

    public static function familyFor(string $ip): IpAddressFamily
    {
        return str_contains($ip, ':') ? IpAddressFamily::V6 : IpAddressFamily::V4;
    }
}
