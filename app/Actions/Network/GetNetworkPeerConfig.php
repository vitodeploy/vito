<?php

namespace App\Actions\Network;

use App\Enums\NetworkServerStatus;
use App\Models\NetworkPeer;
use App\Models\NetworkServer;

class GetNetworkPeerConfig
{
    private const PRIVATE_KEY_PLACEHOLDER = 'REPLACE_WITH_YOUR_PRIVATE_KEY';

    /**
     * The configuration is always available so it can be regenerated after the network's
     * membership changes. The private key is returned separately and only while Vito still
     * holds it — once concealed, the config renders a placeholder in its place.
     *
     * @return array{config: string, private_key: ?string}
     */
    public function config(NetworkPeer $peer): array
    {
        $hasKey = $peer->hasPrivateKey();

        $config = view('wireguard.peer-conf', [
            'address' => $peer->ip,
            'privateKey' => $hasKey ? $peer->private_key : self::PRIVATE_KEY_PLACEHOLDER,
            'peers' => $this->peers($peer),
        ])->render();

        return [
            'config' => $config,
            'private_key' => $hasKey ? $peer->private_key : null,
        ];
    }

    /**
     * @return array<int, array{public_key: string, allowed_ips: string, endpoint: string}>
     */
    private function peers(NetworkPeer $peer): array
    {
        $network = $peer->network;

        $members = $network->servers()
            ->where('status', '!=', NetworkServerStatus::LEAVING)
            ->whereNotNull('public_key')
            ->whereNotNull('ip')
            ->with('server')
            ->get()
            ->filter(fn (NetworkServer $member): bool => filled($member->server->ip))
            ->values();

        return $members
            ->map(fn (NetworkServer $member, int $index): array => [
                'public_key' => (string) $member->public_key,
                'allowed_ips' => $index === 0 ? (string) $network->cidr : $member->ip.'/32',
                'endpoint' => $member->server->ip.':'.$network->port,
            ])
            ->all();
    }
}
