<?php

namespace App\Actions\Network;

use App\Enums\NetworkServerStatus;
use App\Models\NetworkPeer;
use App\Models\NetworkServer;

class GetNetworkPeerConfig
{
    private const PRIVATE_KEY_PLACEHOLDER = 'REPLACE_WITH_YOUR_PRIVATE_KEY';

    /**
     * @return array{config: string}
     */
    public function config(NetworkPeer $peer): array
    {
        abort_unless($peer->canShowConfig(), 410);

        $network = $peer->network;

        $config = view('wireguard.peer-conf', [
            'address' => $peer->ip,
            'privateKey' => $peer->byo ? self::PRIVATE_KEY_PLACEHOLDER : $peer->private_key,
            'peers' => $this->peers($peer),
        ])->render();

        return ['config' => $config];
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
