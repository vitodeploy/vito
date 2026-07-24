<?php

namespace App\Actions\Network;

use App\Enums\IpAddressType;
use App\Enums\NetworkServerStatus;
use App\Enums\NetworkType;
use App\Models\Network;
use App\Models\Server;
use App\Support\Cidr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AddServersToNetwork
{
    public function __construct(
        private GenerateWireGuardKeys $keys,
        private DispatchNetworkServerSync $sync,
        private RecomputeNetworkStatus $recompute,
        private ApplyNetworkFirewall $firewall,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function add(Network $network, array $input): void
    {
        if ($network->type === NetworkType::PROVIDER) {
            throw ValidationException::withMessages([
                'servers' => __('Members of a provider-managed network are synced from the provider.'),
            ]);
        }

        $this->validate($network, $input);

        $newMemberIds = DB::transaction(function () use ($network, $input): array {
            return $network->type === NetworkType::WIREGUARD
                ? $this->addWireGuard($network, $input)
                : $this->addCustom($network, $input);
        });

        if ($network->type === NetworkType::WIREGUARD) {
            $network->load('servers.server');
            foreach ($network->servers as $member) {
                if (in_array($member->id, $newMemberIds, true)
                    || in_array($member->status, [NetworkServerStatus::ACTIVE, NetworkServerStatus::UPDATING], true)) {
                    $this->sync->toPresent($member);
                }
            }
        } else {
            $this->firewall->handle($network);
        }

        $this->recompute->handle($network);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<int, int>
     */
    private function addWireGuard(Network $network, array $input): array
    {
        Network::query()->whereKey($network->id)->lockForUpdate()->first();

        $used = $network->servers()->lockForUpdate()->pluck('ip')
            ->concat($network->peers()->lockForUpdate()->pluck('ip'))
            ->filter()
            ->values()
            ->all();

        $servers = Server::query()
            ->where('project_id', $network->project_id)
            ->whereIn('id', $input['servers'])
            ->get();

        $ids = [];
        foreach ($servers as $server) {
            $ip = Cidr::nextHost((string) $network->cidr, $used);
            if ($ip === null) {
                throw ValidationException::withMessages([
                    'servers' => __('The network address block is full.'),
                ]);
            }
            $used[] = $ip;

            $keys = $this->keys->generate();
            $member = $network->servers()->create([
                'server_id' => $server->id,
                'ip' => $ip,
                'public_key' => $keys['public_key'],
                'private_key' => $keys['private_key'],
                'status' => NetworkServerStatus::PENDING,
            ]);
            $ids[] = $member->id;
        }

        return $ids;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<int, int>
     */
    private function addCustom(Network $network, array $input): array
    {
        $ids = [];
        foreach ($input['servers'] as $serverId) {
            $member = $network->servers()->create([
                'server_id' => $serverId,
                'server_ip_address_id' => $input['ip_addresses'][$serverId],
                'status' => NetworkServerStatus::ACTIVE,
            ]);
            $ids[] = $member->id;
        }

        return $ids;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function validate(Network $network, array $input): void
    {
        $rules = [
            'servers' => ['required', 'array', 'min:1'],
            'servers.*' => [
                'integer',
                'distinct',
                Rule::exists('servers', 'id')->where('project_id', $network->project_id),
                Rule::unique('network_servers', 'server_id')->where('network_id', $network->id),
            ],
        ];

        if ($network->type === NetworkType::CUSTOM) {
            $rules['ip_addresses'] = ['required', 'array'];
            foreach ($input['servers'] ?? [] as $serverId) {
                $rules["ip_addresses.$serverId"] = [
                    'required',
                    Rule::exists('server_ip_addresses', 'id')
                        ->where('server_id', $serverId)
                        ->where('type', IpAddressType::PRIVATE->value),
                    Rule::unique('network_servers', 'server_ip_address_id'),
                ];
            }
        }

        Validator::make($input, $rules)->validate();

        if ($network->type === NetworkType::WIREGUARD) {
            $this->validateNoPortConflict($network, $input['servers'] ?? []);
        }
    }

    /**
     * @param  array<int, int>  $serverIds
     */
    private function validateNoPortConflict(Network $network, array $serverIds): void
    {
        $conflict = Network::query()
            ->where('type', NetworkType::WIREGUARD)
            ->whereKeyNot($network->id)
            ->where('port', $network->port)
            ->whereHas('servers', fn ($query) => $query->whereIn('server_id', $serverIds))
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'servers' => __('A selected server already belongs to another WireGuard network using port :port.', ['port' => $network->port]),
            ]);
        }
    }
}
