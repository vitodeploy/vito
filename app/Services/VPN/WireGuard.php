<?php

namespace App\Services\VPN;

use App\Enums\NetworkServerStatus;
use App\Exceptions\SSHError;
use App\Helpers\SSH;
use App\Models\Network;
use App\Models\NetworkServer;
use App\Services\AbstractService;
use App\Support\Testing\SSHFake;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
                view('ssh.wireguard.install'),
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
            view('ssh.wireguard.uninstall'),
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

        $content = view('ssh.wireguard.conf', [
            'address' => $membership->ip,
            'prefix' => $this->prefix($network),
            'listenPort' => $network->port,
            'privateKey' => $membership->private_key,
            'peers' => $this->peers($membership),
        ])->render();

        $ssh = $this->service->server->ssh()->setLog($this->service->log);
        $ssh->exec('sudo mkdir -p /etc/wireguard && sudo chmod 700 /etc/wireguard', 'configure-wireguard');
        $this->uploadConf($ssh, $this->confPath($network).'.tmp', $content);

        $ssh->exec(
            view('ssh.wireguard.configure', ['networkId' => $network->id]),
            'configure-wireguard'
        );
    }

    /**
     * @throws SSHError
     */
    public function removeNetwork(Network $network): void
    {
        $this->service->server->ssh()->exec(
            view('ssh.wireguard.remove-network', ['networkId' => $network->id]),
            'remove-wireguard-network'
        );
    }

    private function confPath(Network $network): string
    {
        return "/etc/wireguard/wg-vito-{$network->id}.conf";
    }

    private function prefix(Network $network): int
    {
        return (int) (explode('/', (string) $network->cidr)[1] ?? 32);
    }

    /**
     * @return array<int, array{public_key: string, allowed_ips: string, endpoint: string}>
     */
    private function peers(NetworkServer $membership): array
    {
        return $membership->network->servers()
            ->where('id', '!=', $membership->id)
            ->where('status', '!=', NetworkServerStatus::LEAVING)
            ->whereNotNull('public_key')
            ->whereNotNull('ip')
            ->with('server')
            ->get()
            ->filter(fn (NetworkServer $peer): bool => filled($peer->server->ip))
            ->map(fn (NetworkServer $peer): array => [
                'public_key' => (string) $peer->public_key,
                'allowed_ips' => $peer->ip.'/32',
                'endpoint' => $peer->server->ip.':'.$membership->network->port,
            ])
            ->values()
            ->all();
    }

    private function uploadConf(SSH|SSHFake $ssh, string $remote, string $content): void
    {
        $tmpName = 'wg-'.Str::random(20);
        $disk = Storage::disk('local');
        $disk->put($tmpName, $content);

        try {
            $ssh->upload($disk->path($tmpName), $remote, 'root');
        } finally {
            $disk->delete($tmpName);
        }
    }
}
