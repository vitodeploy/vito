<?php

namespace App\Actions\FirewallRule;

use App\Support\Cidr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GetCloudflareIps
{
    /**
     * Cloudflare's published ranges, used when the live list can't be fetched.
     */
    private const array FALLBACK = [
        '173.245.48.0/20',
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '141.101.64.0/18',
        '108.162.192.0/18',
        '190.93.240.0/20',
        '188.114.96.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '162.158.0.0/15',
        '104.16.0.0/13',
        '104.24.0.0/14',
        '172.64.0.0/13',
        '131.0.72.0/22',
        '2400:cb00::/32',
        '2606:4700::/32',
        '2803:f800::/32',
        '2405:b500::/32',
        '2405:8100::/32',
        '2a06:98c0::/29',
        '2c0f:f248::/32',
    ];

    /**
     * @return array<int, string>
     */
    public function handle(): array
    {
        $cached = Cache::get('firewall:cloudflare-ips');
        if (is_array($cached)) {
            return $cached;
        }

        try {
            $result = Http::timeout(5)->get('https://api.cloudflare.com/client/v4/ips')->throw()->json('result');
            $ips = collect([...($result['ipv4_cidrs'] ?? []), ...($result['ipv6_cidrs'] ?? [])])
                ->filter(fn (mixed $cidr): bool => is_string($cidr) && Cidr::isValid($cidr) && Cidr::prefix($cidr) >= 1)
                ->values()
                ->all();
        } catch (Throwable $e) {
            Log::warning('Failed to fetch Cloudflare IP ranges, using the bundled list.', ['error' => $e->getMessage()]);
            $ips = [];
        }

        Cache::put('firewall:cloudflare-ips', $ips ?: self::FALLBACK, $ips === [] ? now()->addMinutes(10) : now()->addDay());

        return $ips ?: self::FALLBACK;
    }
}
