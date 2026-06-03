<?php

namespace App\Models;

use App\Enums\IpAddressFamily;
use App\Enums\IpAddressStatus;
use App\Enums\IpAddressType;
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
