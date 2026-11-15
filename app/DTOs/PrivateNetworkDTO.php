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

    private static function normalizeCidr(?string $cidr): ?string
    {
        if ($cidr === null || $cidr === '') {
            return null;
        }

        return Cidr::isValid($cidr) ? Cidr::canonical($cidr) : null;
    }
}
