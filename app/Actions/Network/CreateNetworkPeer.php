<?php

namespace App\Actions\Network;

use App\DTOs\SocketEventDTO;
use App\Enums\NetworkPeerStatus;
use App\Enums\NetworkType;
use App\Events\SocketEvent;
use App\Http\Resources\NetworkPeerResource;
use App\Models\Network;
use App\Models\NetworkPeer;
use App\Support\Cidr;
use App\ValidationRules\WireGuardPublicKeyRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateNetworkPeer
{
    public function __construct(
        private GenerateWireGuardKeys $keys,
        private DispatchNetworkServerSync $sync,
        private RecomputeNetworkStatus $recompute,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function create(Network $network, array $input): NetworkPeer
    {
        abort_unless($network->type === NetworkType::WIREGUARD, 404);

        $this->validate($network, $input);

        $peer = DB::transaction(function () use ($network, $input): NetworkPeer {
            Network::query()->whereKey($network->id)->lockForUpdate()->first();

            $used = $this->usedIps($network);
            $ip = Cidr::nextHost((string) $network->cidr, $used);
            if ($ip === null) {
                throw ValidationException::withMessages([
                    'name' => __('The network address block is full.'),
                ]);
            }

            return $network->peers()->create([
                ...$this->keyAttributes($input),
                'name' => $input['name'],
                'ip' => $ip,
                'status' => NetworkPeerStatus::PENDING,
            ]);
        });

        $this->sync->resyncMembers($network);
        $this->recompute->handle($network);

        $this->broadcast($network, $peer);

        return $peer;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{public_key: string, private_key: ?string, byo: bool}
     */
    private function keyAttributes(array $input): array
    {
        $publicKey = $input['public_key'] ?? null;

        if (is_string($publicKey) && $publicKey !== '') {
            return ['public_key' => $publicKey, 'private_key' => null, 'byo' => true];
        }

        $keys = $this->keys->generate();

        return ['public_key' => $keys['public_key'], 'private_key' => $keys['private_key'], 'byo' => false];
    }

    /**
     * @return array<int, string>
     */
    private function usedIps(Network $network): array
    {
        return $network->servers()->lockForUpdate()->pluck('ip')
            ->concat($network->peers()->lockForUpdate()->pluck('ip'))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function validate(Network $network, array $input): void
    {
        Validator::make($input, [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('network_peers', 'name')->where('network_id', $network->id),
            ],
            'public_key' => ['nullable', 'string', new WireGuardPublicKeyRule($network)],
        ])->validate();
    }

    private function broadcast(Network $network, NetworkPeer $peer): void
    {
        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $network->project_id,
            type: 'network-peer.updated',
            data: new NetworkPeerResource($peer),
        ));
    }
}
