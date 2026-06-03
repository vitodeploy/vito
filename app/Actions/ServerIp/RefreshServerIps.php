<?php

namespace App\Actions\ServerIp;

use App\Enums\IpAddressFamily;
use App\Enums\IpAddressStatus;
use App\Exceptions\SSHCommandError;
use App\Exceptions\SSHError;
use App\Models\Server;
use App\Models\ServerIpAddress;
use Illuminate\Support\Facades\DB;
use Throwable;

class RefreshServerIps
{
    private const MANAGED_SENTINEL = '===VITO-MANAGED===';

    /**
     * Read the server's configured addresses and reconcile the catalogue.
     *
     * An empty result is treated as a no-op (the existing catalogue is kept)
     * rather than a reconcile, so a transient empty or garbled SSH read can
     * never wipe the previously discovered rows.
     *
     * @throws SSHError
     * @throws Throwable
     */
    public function handle(Server $server): void
    {
        $output = $server->ssh()->exec(
            view('ssh.network.list-network-addresses'),
            'list-network-addresses'
        );

        [$addressOutput, $managedOutput] = $this->split($output);

        $discovered = $this->parse($addressOutput);

        if ($discovered === []) {
            return;
        }

        $this->reconcile($server, $discovered, $this->parseManaged($managedOutput));
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function split(string $output): array
    {
        $position = strpos($output, self::MANAGED_SENTINEL);

        if ($position === false) {
            return [$output, ''];
        }

        return [
            substr($output, 0, $position),
            substr($output, $position + strlen(self::MANAGED_SENTINEL)),
        ];
    }

    /**
     * Addresses Vito previously added are recorded in a `# vito-managed:` marker
     * inside its netplan file so they keep their managed status on rediscovery.
     *
     * @return array<int, string>
     */
    private function parseManaged(string $output): array
    {
        if (preg_match('/#\s*vito-managed:\s*(.+)/', $output, $matches) !== 1) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $matches[1]))));
    }

    /**
     * @return array<int, array{ip: string, prefix_length: int, family: string, interface: string, dynamic: bool}>
     *
     * @throws SSHCommandError
     */
    private function parse(string $output): array
    {
        $interfaces = json_decode(trim($output), true);

        if (! is_array($interfaces)) {
            throw new SSHCommandError(message: 'Unexpected output while reading server IP addresses.');
        }

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

                if (! in_array($family, ['inet', 'inet6'], true)
                    || ! is_string($local)
                    || filter_var($local, FILTER_VALIDATE_IP) === false
                    || $scope !== 'global') {
                    continue;
                }

                $discovered[] = [
                    'ip' => $local,
                    'prefix_length' => max(1, min(128, (int) ($address['prefixlen'] ?? 32))),
                    'family' => $family,
                    'interface' => $name,
                    'dynamic' => (bool) ($address['dynamic'] ?? false),
                ];
            }
        }

        return $discovered;
    }

    /**
     * @param  array<int, array{ip: string, prefix_length: int, family: string, interface: string, dynamic: bool}>  $discovered
     * @param  array<int, string>  $managedIps
     *
     * @throws Throwable
     */
    private function reconcile(Server $server, array $discovered, array $managedIps): void
    {
        DB::transaction(function () use ($server, $discovered, $managedIps): void {
            $primaryIps = array_filter([$server->ip, $server->local_ip]);
            $existing = $server->ipAddresses()->get()->keyBy('ip');
            $seen = [];

            foreach ($discovered as $entry) {
                $seen[] = $entry['ip'];
                $isPrimary = in_array($entry['ip'], $primaryIps, true);
                $isManaged = in_array($entry['ip'], $managedIps, true);

                /** @var ?ServerIpAddress $row */
                $row = $existing->get($entry['ip']);

                if ($row instanceof ServerIpAddress) {
                    if ($row->is_managed) {
                        $row->interface = $entry['interface'];
                        $row->prefix_length = $entry['prefix_length'];
                        $row->family = IpAddressFamily::from($entry['family']);
                        $row->type = ServerIpAddress::classifyType($entry['ip']);
                        $row->is_primary = $isPrimary;
                        $row->is_dynamic = $entry['dynamic'];
                        $row->save();

                        continue;
                    }

                    $row->interface = $entry['interface'];
                    $row->prefix_length = $entry['prefix_length'];
                    $row->family = IpAddressFamily::from($entry['family']);
                    $row->type = ServerIpAddress::classifyType($entry['ip']);
                    $row->status = IpAddressStatus::CONFIGURED;
                    $row->is_primary = $isPrimary;
                    $row->is_dynamic = $entry['dynamic'];
                    $row->is_managed = $isManaged;
                    $row->save();

                    continue;
                }

                $row = new ServerIpAddress([
                    'ip' => $entry['ip'],
                    'prefix_length' => $entry['prefix_length'],
                    'family' => IpAddressFamily::from($entry['family']),
                    'interface' => $entry['interface'],
                ]);
                $row->server_id = $server->id;
                $row->type = ServerIpAddress::classifyType($entry['ip']);
                $row->status = IpAddressStatus::CONFIGURED;
                $row->is_managed = $isManaged;
                $row->is_primary = $isPrimary;
                $row->is_dynamic = $entry['dynamic'];
                $row->save();
            }

            $server->ipAddresses()
                ->where('is_managed', false)
                ->whereNotIn('ip', $seen)
                ->delete();
        });
    }
}
