<?php

namespace App\Actions\Network;

use App\Enums\NetworkAddressingPool;
use App\Models\Server;
use App\Models\ServerIpAddress;
use App\Support\Cidr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AllocateNetworkBlock
{
    /**
     * Provider/Docker/AWS/Linode ranges to avoid on the RFC1918 opt-in path.
     * The whole 172.16.0.0/12 (Docker/AWS default) is excluded by not listing
     * it as an RFC1918 supernet below.
     *
     * @var array<int, string>
     */
    private const BLOCKLIST = [
        '10.244.0.0/16',
        '10.245.0.0/16',
        '10.246.0.0/24',
        '10.229.0.0/16',
        '192.168.128.0/17',
    ];

    /**
     * @return array<int, string>
     */
    private function supernets(NetworkAddressingPool $pool): array
    {
        return match ($pool) {
            NetworkAddressingPool::CGNAT => ['100.64.0.0/10'],
            NetworkAddressingPool::RFC1918 => ['10.0.0.0/8', '192.168.0.0/16'],
        };
    }

    /**
     * Carve the next free canonical block from the pool, avoiding overlap with
     * existing project networks and with any member server's observed subnets
     * (of any type — a CGNAT-WAN interface is stored PUBLIC).
     *
     * @param  Collection<int, string>  $existingCidrs
     * @param  Collection<int, Server>  $memberServers
     */
    public function allocate(
        NetworkAddressingPool $pool,
        int $blockPrefix,
        Collection $existingCidrs,
        Collection $memberServers
    ): string {
        $existing = $existingCidrs->filter()->values()->all();
        $memberSubnets = $this->memberSubnets($memberServers);
        $blocklist = $pool === NetworkAddressingPool::RFC1918 ? self::BLOCKLIST : [];

        foreach ($this->supernets($pool) as $supernet) {
            $candidate = $this->scan($supernet, $blockPrefix, $existing, $memberSubnets, $blocklist);
            if ($candidate !== null) {
                return $candidate;
            }
        }

        throw ValidationException::withMessages([
            'servers' => __('No free address block is available in the selected pool. Choose a smaller block size or the RFC1918 pool.'),
        ]);
    }

    /**
     * @param  array<int, string>  $existing
     * @param  array<int, string>  $memberSubnets
     * @param  array<int, string>  $blocklist
     */
    private function scan(
        string $supernet,
        int $blockPrefix,
        array $existing,
        array $memberSubnets,
        array $blocklist
    ): ?string {
        $supernetPrefix = Cidr::prefix($supernet);
        if ($blockPrefix < $supernetPrefix) {
            return null;
        }

        $base = Cidr::toLong(Cidr::network($supernet));
        $blockSize = Cidr::size($blockPrefix);
        $count = 2 ** ($blockPrefix - $supernetPrefix);

        for ($i = 0; $i < $count; $i++) {
            $candidate = long2ip($base + ($i * $blockSize)).'/'.$blockPrefix;

            if ($this->conflicts($candidate, $existing) || $this->conflicts($candidate, $blocklist)
                || $this->conflicts($candidate, $memberSubnets)) {
                continue;
            }

            return $candidate;
        }

        return null;
    }

    /**
     * @param  array<int, string>  $others
     */
    private function conflicts(string $candidate, array $others): bool
    {
        foreach ($others as $other) {
            if (Cidr::overlaps($candidate, $other)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  Collection<int, Server>  $memberServers
     * @return array<int, string>
     */
    private function memberSubnets(Collection $memberServers): array
    {
        return ServerIpAddress::query()
            ->whereIn('server_id', $memberServers->pluck('id')->all())
            ->get()
            ->filter(fn (ServerIpAddress $address): bool => filter_var($address->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false)
            ->map(fn (ServerIpAddress $address): string => Cidr::canonical($address->ip.'/'.$address->prefix_length))
            ->unique()
            ->values()
            ->all();
    }
}
