<?php

namespace App\Actions\ServerIp;

use App\Enums\IpAddressStatus;
use App\Exceptions\SSHError;
use App\Models\Server;
use App\Models\ServerIpAddress;
use Illuminate\Support\Facades\DB;
use Throwable;

class RefreshServerIps
{
    /**
     * @throws SSHError
     */
    public function handle(Server $server): void
    {
        $output = $server->ssh()->exec(
            view('ssh.network.list-ips'),
            'list-ips'
        );

        $discovered = $this->parse($output);

        if ($discovered === []) {
            return;
        }

        $this->reconcile($server, $discovered);
    }

    /**
     * @return array<int, array{ip: string, prefix_length: int, family: string, interface: string, dynamic: bool}>
     */
    private function parse(string $output): array
    {
        /** @var array<int, array<string, mixed>> $interfaces */
        $interfaces = json_decode($output, true) ?: [];

        $discovered = [];
        foreach ($interfaces as $interface) {
            $name = $interface['ifname'] ?? null;
            if (! is_string($name) || $name === 'lo' || preg_match('/^[A-Za-z0-9@._-]+$/', $name) !== 1) {
                continue;
            }

            foreach ($interface['addr_info'] ?? [] as $address) {
                $family = $address['family'] ?? null;
                $local = $address['local'] ?? null;
                $scope = $address['scope'] ?? null;

                if (! in_array($family, ['inet', 'inet6'], true) || ! is_string($local) || $scope !== 'global') {
                    continue;
                }

                $discovered[] = [
                    'ip' => $local,
                    'prefix_length' => (int) ($address['prefixlen'] ?? 32),
                    'family' => $family,
                    'interface' => $name,
                    'dynamic' => (bool) ($address['dynamic'] ?? false),
                ];
            }
        }

        return $discovered;
    }

    /**
     * @param array<int, array{ip: string, prefix_length: int, family: string, interface: string, dynamic: bool}> $discovered
     * @throws Throwable
     */
    private function reconcile(Server $server, array $discovered): void
    {
        DB::transaction(function () use ($server, $discovered): void {
            $primaryIps = array_filter([$server->ip, $server->local_ip]);
            $existing = $server->ipAddresses()->get()->keyBy('ip');
            $seen = [];

            foreach ($discovered as $entry) {
                $seen[] = $entry['ip'];
                $isPrimary = in_array($entry['ip'], $primaryIps, true);

                /** @var ?ServerIpAddress $row */
                $row = $existing->get($entry['ip']);

                if ($row instanceof ServerIpAddress) {
                    if ($row->is_managed) {
                        $row->update([
                            'interface' => $entry['interface'],
                            'is_primary' => $isPrimary,
                        ]);

                        continue;
                    }

                    $row->update([
                        'interface' => $entry['interface'],
                        'prefix_length' => $entry['prefix_length'],
                        'family' => $entry['family'],
                        'type' => ServerIpAddress::classifyType($entry['ip']),
                        'status' => IpAddressStatus::CONFIGURED,
                        'is_primary' => $isPrimary,
                        'is_dynamic' => $entry['dynamic'],
                    ]);

                    continue;
                }

                $server->ipAddresses()->create([
                    'ip' => $entry['ip'],
                    'prefix_length' => $entry['prefix_length'],
                    'family' => $entry['family'],
                    'interface' => $entry['interface'],
                    'type' => ServerIpAddress::classifyType($entry['ip']),
                    'status' => IpAddressStatus::CONFIGURED,
                    'is_managed' => false,
                    'is_primary' => $isPrimary,
                    'is_dynamic' => $entry['dynamic'],
                ]);
            }

            $server->ipAddresses()
                ->where('is_managed', false)
                ->whereNotIn('ip', $seen)
                ->delete();
        });
    }
}
