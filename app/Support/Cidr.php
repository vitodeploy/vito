<?php

namespace App\Support;

class Cidr
{
    public const V4_BITS = 32;

    public const V6_BITS = 128;

    public static function bits(string $ipOrCidr): int
    {
        $bytes = self::toBytes(self::address($ipOrCidr));

        return $bytes !== null && strlen($bytes) === 16 ? self::V6_BITS : self::V4_BITS;
    }

    public static function isV6(string $ipOrCidr): bool
    {
        return self::bits($ipOrCidr) === self::V6_BITS;
    }

    public static function hostPrefix(string $ipOrCidr): int
    {
        return self::bits($ipOrCidr);
    }

    public static function isValidAddress(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    public static function isValid(string $cidr): bool
    {
        $parts = explode('/', $cidr);

        if (count($parts) !== 2 || ! ctype_digit($parts[1]) || ! self::isValidAddress($parts[0])) {
            return false;
        }

        return (int) $parts[1] <= self::bits($parts[0]);
    }

    /**
     * @return array{0: string, 1: int}
     */
    public static function split(string $cidr): array
    {
        $parts = explode('/', $cidr);
        $address = $parts[0];
        $prefix = $parts[1] ?? '';
        $bits = self::bits($address);

        return [$address, ctype_digit($prefix) ? min($bits, (int) $prefix) : $bits];
    }

    public static function prefix(string $cidr): int
    {
        return self::split($cidr)[1];
    }

    public static function address(string $ipOrCidr): string
    {
        return explode('/', $ipOrCidr)[0];
    }

    public static function network(string $cidr): string
    {
        [$address, $prefix] = self::split($cidr);
        $bytes = self::toBytes($address);

        if ($bytes === null) {
            return $address;
        }

        return self::fromBytes(self::mask($bytes, $prefix));
    }

    public static function canonical(string $cidr): string
    {
        return self::network($cidr).'/'.self::prefix($cidr);
    }

    public static function contains(string $cidr, string $ip): bool
    {
        [$address, $prefix] = self::split($cidr);

        $network = self::toBytes($address);
        $candidate = self::toBytes($ip);

        if ($network === null || $candidate === null || strlen($network) !== strlen($candidate)) {
            return false;
        }

        return self::mask($candidate, $prefix) === self::mask($network, $prefix);
    }

    public static function containsRange(string $outer, string $inner): bool
    {
        if (self::bits($outer) !== self::bits($inner)) {
            return false;
        }

        return self::prefix($inner) >= self::prefix($outer)
            && self::contains($outer, self::network($inner));
    }

    public static function overlaps(string $a, string $b): bool
    {
        if (self::bits($a) !== self::bits($b)) {
            return false;
        }

        return self::contains($a, self::network($b)) || self::contains($b, self::network($a));
    }

    /**
     * @param  array<int, string>  $used
     */
    public static function nextHost(string $cidr, array $used): ?string
    {
        [$address, $prefix] = self::split($cidr);
        $bytes = self::toBytes($address);

        if ($bytes === null) {
            return null;
        }

        $network = self::mask($bytes, $prefix);
        $used = array_flip($used);

        for ($index = 2; ; $index++) {
            $candidate = self::add($network, $index);

            if ($candidate === null || self::mask($candidate, $prefix) !== $network) {
                return null;
            }

            if (strlen($candidate) === 4 && self::isAllOnes($candidate, $prefix)) {
                return null;
            }

            $host = self::fromBytes($candidate);

            if (! isset($used[$host])) {
                return $host;
            }
        }
    }

    public static function endpoint(string $ip, int|string $port): string
    {
        return self::isV6($ip) ? '['.$ip.']:'.$port : $ip.':'.$port;
    }

    public static function size(int $prefix): int
    {
        return 2 ** (self::V4_BITS - $prefix);
    }

    public static function toLong(string $ip): int
    {
        return (int) sprintf('%u', ip2long($ip));
    }

    private static function toBytes(string $ip): ?string
    {
        $bytes = @inet_pton($ip);

        return $bytes === false ? null : $bytes;
    }

    private static function fromBytes(string $bytes): string
    {
        $ip = @inet_ntop($bytes);

        return $ip === false ? '' : $ip;
    }

    private static function mask(string $bytes, int $prefix): string
    {
        $length = strlen($bytes);

        for ($i = 0; $i < $length; $i++) {
            $keep = $prefix - ($i * 8);

            if ($keep >= 8) {
                continue;
            }

            $bytes[$i] = $keep <= 0
                ? "\0"
                : chr(ord($bytes[$i]) & ((0xFF << (8 - $keep)) & 0xFF));
        }

        return $bytes;
    }

    private static function isAllOnes(string $bytes, int $prefix): bool
    {
        $length = strlen($bytes);

        for ($i = 0; $i < $length; $i++) {
            $keep = $prefix - ($i * 8);

            if ($keep >= 8) {
                continue;
            }

            $hostMask = $keep <= 0 ? 0xFF : (~(0xFF << (8 - $keep))) & 0xFF;

            if ((ord($bytes[$i]) & $hostMask) !== $hostMask) {
                return false;
            }
        }

        return true;
    }

    private static function add(string $bytes, int $offset): ?string
    {
        for ($i = strlen($bytes) - 1; $i >= 0 && $offset > 0; $i--) {
            $sum = ord($bytes[$i]) + ($offset & 0xFF);
            $bytes[$i] = chr($sum & 0xFF);
            $offset = ($offset >> 8) + ($sum >> 8);
        }

        return $offset > 0 ? null : $bytes;
    }
}
