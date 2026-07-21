<?php

namespace App\Support;

class Cidr
{
    public static function toLong(string $ip): int
    {
        return (int) sprintf('%u', ip2long($ip));
    }

    public static function mask(int $prefix): int
    {
        if ($prefix <= 0) {
            return 0;
        }

        return (0xFFFFFFFF << (32 - $prefix)) & 0xFFFFFFFF;
    }

    public static function base(string $cidr): int
    {
        [$ip, $prefix] = self::split($cidr);

        return self::toLong($ip) & self::mask($prefix);
    }

    /**
     * @return array{0: string, 1: int}
     */
    public static function split(string $cidr): array
    {
        $parts = explode('/', $cidr);

        return [$parts[0], (int) ($parts[1] ?? 32)];
    }

    public static function prefix(string $cidr): int
    {
        return self::split($cidr)[1];
    }

    public static function canonical(string $cidr): string
    {
        $prefix = self::prefix($cidr);

        return long2ip(self::base($cidr)).'/'.$prefix;
    }

    public static function size(int $prefix): int
    {
        return 2 ** (32 - $prefix);
    }

    public static function contains(string $cidr, string $ip): bool
    {
        [, $prefix] = self::split($cidr);
        $mask = self::mask($prefix);

        return (self::toLong($ip) & $mask) === self::base($cidr);
    }

    public static function overlaps(string $a, string $b): bool
    {
        return self::contains($a, long2ip(self::base($b)))
            || self::contains($b, long2ip(self::base($a)));
    }

    public static function nthHost(string $cidr, int $index): string
    {
        return long2ip(self::base($cidr) + $index);
    }

    /**
     * First usable host (index 2), skipping the network address and the
     * reserved gateway (.1), avoiding any already-used address and the
     * broadcast address. Returns null when the block is exhausted.
     *
     * @param  array<int, string>  $used
     */
    public static function nextHost(string $cidr, array $used): ?string
    {
        $prefix = self::prefix($cidr);
        $size = self::size($prefix);
        $used = array_flip($used);

        for ($index = 2; $index < $size - 1; $index++) {
            $host = self::nthHost($cidr, $index);
            if (! isset($used[$host])) {
                return $host;
            }
        }

        return null;
    }
}
