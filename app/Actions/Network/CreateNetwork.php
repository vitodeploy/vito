<?php

namespace App\Actions\Network;

use App\Enums\FirewallRuleStatus;
use App\Enums\IpAddressType;
use App\Enums\NetworkAddressingPool;
use App\Enums\NetworkServerStatus;
use App\Enums\NetworkStatus;
use App\Enums\NetworkType;
use App\Models\Network;
use App\Models\Project;
use App\Models\Server;
use App\Support\Cidr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateNetwork
{
    public function __construct(
        private AllocateNetworkBlock $allocator,
        private GenerateWireGuardKeys $keys,
        private DispatchNetworkServerSync $sync,
        private RecomputeNetworkStatus $recompute,
        private ApplyNetworkFirewall $firewall,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function create(Project $project, array $input): Network
    {
        $this->validate($project, $input);

        $type = NetworkType::from($input['type']);

        $network = DB::transaction(function () use ($type, $project, $input): Network {
            $network = $type === NetworkType::WIREGUARD
                ? $this->buildWireGuard($project, $input)
                : $this->buildCustom($project, $input);

            $this->seedDefaultFirewallRule($network);

            return $network;
        });

        if ($network->type === NetworkType::WIREGUARD) {
            $network->load('servers.server');
            foreach ($network->servers as $member) {
                $this->sync->toPresent($member);
            }
        } else {
            $this->firewall->handle($network);
        }

        $this->recompute->handle($network);

        return $network->refresh();
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function buildWireGuard(Project $project, array $input): Network
    {
        $pool = NetworkAddressingPool::from($input['addressing_pool'] ?? NetworkAddressingPool::CGNAT->value);
        $blockPrefix = (int) ($input['prefix'] ?? 24);

        Project::query()->whereKey($project->id)->lockForUpdate()->first();

        /** @var Collection<int, Server> $members */
        $members = Server::query()
            ->where('project_id', $project->id)
            ->whereIn('id', $input['servers'])
            ->get();

        $existing = $project->networks()->pluck('cidr_canonical');

        $cidr = $this->allocator->allocate($pool, $blockPrefix, $existing, $members);

        $network = Network::create([
            'project_id' => $project->id,
            'name' => $input['name'],
            'type' => NetworkType::WIREGUARD,
            'status' => NetworkStatus::CREATING,
            'addressing_pool' => $pool,
            'cidr' => $cidr,
            'cidr_canonical' => $cidr,
            'port' => $this->allocatePort($project, $members->pluck('id')->all(), (int) ($input['port'] ?? 51820)),
        ]);

        $used = [];
        foreach ($members as $server) {
            $ip = Cidr::nextHost($cidr, $used);
            if ($ip === null) {
                throw ValidationException::withMessages([
                    'servers' => __('The network address block is full.'),
                ]);
            }
            $used[] = $ip;

            $keys = $this->keys->generate();
            $network->servers()->create([
                'server_id' => $server->id,
                'ip' => $ip,
                'public_key' => $keys['public_key'],
                'private_key' => $keys['private_key'],
                'status' => NetworkServerStatus::PENDING,
            ]);
        }

        return $network;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function buildCustom(Project $project, array $input): Network
    {
        Project::query()->whereKey($project->id)->lockForUpdate()->first();

        if (isset($input['cidr']) && $input['cidr'] !== '') {
            $canonical = Cidr::canonical($input['cidr']);
            if ($project->networks()
                ->whereIn('type', [NetworkType::CUSTOM, NetworkType::WIREGUARD])
                ->where('cidr_canonical', $canonical)
                ->exists()) {
                throw ValidationException::withMessages([
                    'cidr' => __('This CIDR is already used by another network in this project.'),
                ]);
            }
        }

        $cidr = ($input['cidr'] ?? '') !== '' ? $input['cidr'] : null;

        $network = Network::create([
            'project_id' => $project->id,
            'name' => $input['name'],
            'type' => NetworkType::CUSTOM,
            'status' => NetworkStatus::ACTIVE,
            'cidr' => $cidr,
            'cidr_canonical' => $cidr !== null ? Cidr::canonical($cidr) : null,
        ]);

        foreach ($input['servers'] as $serverId) {
            $network->servers()->create([
                'server_id' => $serverId,
                'server_ip_address_id' => $input['ip_addresses'][$serverId],
                'status' => NetworkServerStatus::ACTIVE,
            ]);
        }

        return $network;
    }

    private function seedDefaultFirewallRule(Network $network): void
    {
        $network->firewallRules()->create([
            'name' => 'Allow all',
            'protocol' => null,
            'port' => null,
            'status' => FirewallRuleStatus::READY,
        ]);
    }

    /**
     * @param  array<int, int>  $serverIds
     */
    private function allocatePort(Project $project, array $serverIds, int $requested): int
    {
        $used = Network::query()
            ->where('type', NetworkType::WIREGUARD)
            ->where('project_id', $project->id)
            ->whereHas('servers', fn ($query) => $query->whereIn('server_id', $serverIds))
            ->pluck('port')
            ->filter()
            ->all();

        $port = $requested;
        while (in_array($port, $used, true)) {
            $port++;
            if ($port > 65535) {
                throw ValidationException::withMessages([
                    'port' => __('No free WireGuard port is available for the selected servers.'),
                ]);
            }
        }

        return $port;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function validate(Project $project, array $input): void
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('networks', 'name')->where('project_id', $project->id),
            ],
            'type' => [
                'required',
                Rule::in([NetworkType::CUSTOM->value, NetworkType::WIREGUARD->value]),
            ],
            'servers' => ['required', 'array', 'min:1'],
            'servers.*' => [
                Rule::exists('servers', 'id')->where('project_id', $project->id),
            ],
        ];

        if (($input['type'] ?? null) === NetworkType::WIREGUARD->value) {
            $rules['addressing_pool'] = [
                'nullable',
                Rule::in([NetworkAddressingPool::CGNAT->value, NetworkAddressingPool::RFC1918->value]),
            ];
            $rules['prefix'] = ['nullable', 'integer', 'min:16', 'max:28'];
            $rules['port'] = ['nullable', 'integer', 'min:1024', 'max:65535'];
        }

        if (($input['type'] ?? null) === NetworkType::CUSTOM->value) {
            $rules['cidr'] = ['nullable', 'string', $this->ipv4CidrRule()];
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
    }

    private function ipv4CidrRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value === null || $value === '') {
                return;
            }

            $parts = explode('/', (string) $value);
            $validPrefix = isset($parts[1]) && ctype_digit($parts[1]) && (int) $parts[1] >= 0 && (int) $parts[1] <= 32;

            if (count($parts) !== 2 || ! $validPrefix
                || filter_var($parts[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
                $fail(__('The :attribute must be a valid IPv4 CIDR.'));
            }
        };
    }
}
