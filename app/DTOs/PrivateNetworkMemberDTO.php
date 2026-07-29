<?php

namespace App\DTOs;

use App\Support\Cidr;

final readonly class PrivateNetworkMemberDTO
{
    public ?string $ip;

    public function __construct(
        public string $instanceId,
        ?string $ip = null,
    ) {
        $this->ip = self::normalizeIp($ip);
    }

    /**
     * Member addresses reach `ServerNetworkRule.source` and are interpolated into the `ufw`
     * blade template, which is a shell script — Blade's escaping is HTML escaping and offers
     * no protection there. Both families are accepted, but only a literal address: anything
     * carrying whitespace, a prefix or shell metacharacters is dropped.
     */
    private static function normalizeIp(?string $ip): ?string
    {
        if ($ip === null || $ip === '') {
            return null;
        }

        return Cidr::isValidAddress($ip) ? $ip : null;
    }
}
