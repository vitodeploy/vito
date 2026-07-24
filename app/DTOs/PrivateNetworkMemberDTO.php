<?php

namespace App\DTOs;

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
     * no protection there. Anything that is not a plain IPv4 address is dropped, matching how
     * `PrivateNetworkDTO` treats provider-reported CIDRs.
     */
    private static function normalizeIp(?string $ip): ?string
    {
        if ($ip === null || $ip === '') {
            return null;
        }

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false ? null : $ip;
    }
}
