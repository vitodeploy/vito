<?php

namespace App\DTOs;

use App\Support\Cidr;

final readonly class PrivateNetworkDTO
{
    /** @var array<int, PrivateNetworkMemberDTO> */
    public array $members;

    public ?string $cidr;

    /**
     * @param  array<int, PrivateNetworkMemberDTO>  $members
     */
    public function __construct(
        public string $externalId,
        public string $name,
        ?string $cidr = null,
        public ?string $region = null,
        array $members = [],
    ) {
        $this->cidr = self::normalizeCidr($cidr);
        $this->members = array_values($members);
    }

    /**
     * Providers may report IPv6 or malformed ranges. `App\Support\Cidr` is IPv4-only
     * (`ip2long`), so anything else is dropped rather than silently stored as 0.0.0.0/x.
     */
    private static function normalizeCidr(?string $cidr): ?string
    {
        if ($cidr === null || $cidr === '') {
            return null;
        }

        $parts = explode('/', $cidr);

        if (count($parts) !== 2 || ! ctype_digit($parts[1])) {
            return null;
        }

        $prefix = (int) $parts[1];

        if ($prefix < 0 || $prefix > 32) {
            return null;
        }

        if (filter_var($parts[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            return null;
        }

        return Cidr::canonical($cidr);
    }
}
