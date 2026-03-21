<?php

namespace App\Actions\HostedDomain;

use App\Models\HostedDomain;
use App\Models\Server;

class CheckDomainResolution
{
    /**
     * Check if the domain resolves to the server's IP address.
     *
     * Queries authoritative nameservers directly to avoid cached/stale results.
     * Falls back to a public resolver (1.1.1.1) if authoritative NS lookup fails.
     *
     * @return array{resolves: bool, resolved_ips: array<int, string>}
     */
    public function check(HostedDomain $hostedDomain, Server $server): array
    {
        $serverIp = $server->ip;

        $output = $server->ssh()->exec(
            view('ssh.dns.check-domain-resolution', [
                'domain' => escapeshellarg($hostedDomain->domain),
            ]),
            'check-domain-resolution',
            $hostedDomain->site_id,
        );

        $resolvedIps = array_filter(
            array_map('trim', explode("\n", trim($output))),
            fn (string $ip) => filter_var($ip, FILTER_VALIDATE_IP) !== false
        );

        return [
            'resolves' => in_array($serverIp, $resolvedIps, true),
            'resolved_ips' => array_values($resolvedIps),
        ];
    }
}
