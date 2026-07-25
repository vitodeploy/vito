<?php

namespace App\Services\VPN;

use App\Enums\NetworkPeerStatus;
use App\Enums\NetworkServerStatus;
use App\Enums\NetworkType;
use App\Exceptions\SSHError;
use App\Helpers\SSH;
use App\Models\Network;
use App\Models\NetworkPeer;
use App\Models\NetworkServer;
use App\Models\ServerLog;
use App\Services\AbstractService;
use App\Support\Cidr;
use App\Support\Testing\SSHFake;
use Closure;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class WireGuard extends AbstractService implements VPN
{
    public static function id(): string
    {
        return 'wireguard';
    }

    public static function type(): string
    {
        return 'vpn';
    }

    /**
     * @return array<string, mixed>
     */
    public function deletionRules(): array
    {
        return [
            'service' => [
                function (string $attribute, mixed $value, Closure $fail): void {
                    $isMember = NetworkServer::query()
                        ->where('server_id', $this->service->server->id)
                        ->where('status', '!=', NetworkServerStatus::LEAVING)
                        ->whereHas('network', fn ($query) => $query->where('type', NetworkType::WIREGUARD))
                        ->exists();

                    if ($isMember) {
                        $fail(__('This server is a member of one or more WireGuard networks. Remove it from those networks before uninstalling WireGuard.'));
                    }
                },
            ],
        ];
    }

    public function unit(): string
    {
        return '';
    }

    /**
     * @throws SSHError
     */
    public function install(): void
    {
        $this->service->server->ssh()
            ->setLog($this->service->log)
            ->exec(
                view('ssh.services.wireguard.install'),
                'install-wireguard'
            );
        event('service.installed', $this->service);
        $this->service->server->os()->cleanup();
    }

    /**
     * @throws SSHError
     */
    public function uninstall(): void
    {
        $this->service->server->ssh()->exec(
            view('ssh.services.wireguard.uninstall'),
            'uninstall-wireguard'
        );
        event('service.uninstalled', $this->service);
    }

    /**
     * @throws SSHError
     */
    public function version(): string
    {
        $version = $this->service->server->ssh()->exec(
            'wg --version | grep -oE \'v[0-9]+\.[0-9]+\.[0-9]+\' | head -n1'
        );

        return trim($version);
    }

    /**
     * @throws SSHError
     */
    public function configureNetwork(NetworkServer $membership): void
    {
        $network = $membership->network;

        $content = view('ssh.services.wireguard.conf', [
            'address' => $membership->ip,
            'prefix' => $this->prefix($network),
            'listenPort' => $network->port,
            'privateKey' => $membership->private_key,
            'peers' => $this->peers($membership),
        ])->render();

        $log = ServerLog::newLog($this->service->server, "configure-wireguard-{$network->id}");
        $log->save();

        $ssh = $this->service->server->ssh()->setLog($log);
        $ssh->exec('sudo mkdir -p /etc/wireguard && sudo chmod 700 /etc/wireguard', 'configure-wireguard');
        $this->uploadConf($ssh, $this->confPath($network).'.tmp', $content);

        $ssh->exec(
            view('ssh.services.wireguard.configure', [
                'networkId' => $network->id,
                'keepNetworkIds' => $this->managedNetworkIds($membership),
            ]),
            'configure-wireguard'
        );
    }

    /**
     * Every WireGuard network this server still belongs to. Any other wg-vito interface on the
     * box is a leftover — a teardown that could not complete while the server was unreachable,
     * for instance — and the configure pass removes it.
     *
     * @return array<int, int>
     */
    private function managedNetworkIds(NetworkServer $membership): array
    {
        return NetworkServer::query()
            ->where('server_id', $membership->server_id)
            ->where('status', '!=', NetworkServerStatus::LEAVING)
            ->whereHas('network', fn ($query) => $query->where('type', NetworkType::WIREGUARD))
            ->pluck('network_id')
            ->push($membership->network_id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @throws SSHError
     */
    public function removeNetwork(Network $network): void
    {
        $this->service->server->ssh()->exec(
            view('ssh.services.wireguard.remove-network', ['networkId' => $network->id]),
            'remove-wireguard-network'
        );
    }

    private function confPath(Network $network): string
    {
        return "/etc/wireguard/wg-vito-{$network->id}.conf";
    }

    private function prefix(Network $network): int
    {
        return Cidr::prefix((string) $network->cidr);
    }

    /**
     * @return array<int, array{public_key: string, allowed_ips: string, endpoint: ?string}>
     */
    private function peers(NetworkServer $membership): array
    {
        $network = $membership->network;

        $servers = $network->servers()
            ->where('id', '!=', $membership->id)
            ->where('status', '!=', NetworkServerStatus::LEAVING)
            ->whereNotNull('public_key')
            ->whereNotNull('ip')
            ->with('server')
            ->get()
            ->filter(fn (NetworkServer $peer): bool => Cidr::isValidAddress((string) $peer->server->ip))
            ->map(fn (NetworkServer $peer): array => [
                'public_key' => (string) $peer->public_key,
                'allowed_ips' => $peer->ip.'/'.Cidr::hostPrefix((string) $peer->ip),
                'endpoint' => Cidr::endpoint((string) $peer->server->ip, (int) $network->port),
            ]);

        $devices = $network->peers()
            ->where('status', '!=', NetworkPeerStatus::DISABLED)
            ->get()
            ->map(fn (NetworkPeer $peer): array => [
                'public_key' => $peer->public_key,
                'allowed_ips' => $peer->ip.'/'.Cidr::hostPrefix($peer->ip),
                'endpoint' => null,
            ]);

        return $servers->concat($devices)->values()->all();
    }

    /**
     * @return array<string, int>
     *
     * @throws SSHError
     */
    public function latestHandshakes(Network $network): array
    {
        $output = $this->service->server->ssh()->exec(
            view('ssh.services.wireguard.latest-handshakes', ['networkId' => $network->id]),
            'wireguard-latest-handshakes'
        );

        $handshakes = [];

        foreach (preg_split('/\r?\n/', trim($output)) ?: [] as $line) {
            $parts = preg_split('/\s+/', trim($line));

            if ($parts === false || count($parts) < 2 || $parts[0] === '') {
                continue;
            }

            $handshakes[$parts[0]] = (int) $parts[1];
        }

        return $handshakes;
    }

    private function uploadConf(SSH|SSHFake $ssh, string $remote, string $content): void
    {
        $tmpName = 'wg-'.Str::random(20);
        $disk = Storage::disk('local');

        try {
            if (! $disk->put($tmpName, '')) {
                throw new RuntimeException('Could not create the temporary WireGuard configuration file.');
            }

            $path = $disk->path($tmpName);

            if (! chmod($path, 0600) || ! $disk->put($tmpName, $content)) {
                throw new RuntimeException('Could not write the temporary WireGuard configuration file.');
            }

            $ssh->upload($path, $remote, 'root');
        } finally {
            $disk->delete($tmpName);
        }
    }
}
