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

    private static function normalizeIp(?string $ip): ?string
    {
        if ($ip === null || $ip === '') {
            return null;
        }

        return Cidr::isValidAddress($ip) ? $ip : null;
    }
}
