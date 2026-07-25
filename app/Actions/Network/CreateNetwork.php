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
use App\ValidationRules\CidrRule;
use App\ValidationRules\PrivateRangeRule;
use App\ValidationRules\WithinCidrRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateNetwork
{
    public function __construct(
        private AllocateNetworkBlock $allocator,
        private AllocateWireGuardPort $ports,
        private CreateWireGuardMembers $members,
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
            'port' => $this->ports->allocate($project->id, $members->pluck('id')->all(), (int) ($input['port'] ?? 51820)),
        ]);

        $this->members->create($network, $members);

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
                'integer',
                'distinct',
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
            $rules['cidr'] = ['nullable', 'string', new CidrRule, new PrivateRangeRule];
            $rules['ip_addresses'] = ['required', 'array'];
        }

        Validator::make($input, $rules)->validate();

        if (($input['type'] ?? null) === NetworkType::CUSTOM->value) {
            $this->validateMemberIps($input);
        }
    }

    /**
     * Runs only once `servers` is known to be a list of integers — building these rules from
     * unvalidated input would interpolate an array into a rule key and fail with a 500.
     *
     * @param  array<string, mixed>  $input
     */
    private function validateMemberIps(array $input): void
    {
        $rules = [];

        foreach ($input['servers'] as $serverId) {
            $rules["ip_addresses.$serverId"] = [
                'required',
                Rule::exists('server_ip_addresses', 'id')
                    ->where('server_id', $serverId)
                    ->where('type', IpAddressType::PRIVATE->value),
                Rule::unique('network_servers', 'server_ip_address_id'),
                new WithinCidrRule($input['cidr'] ?? null),
            ];
        }

        Validator::make($input, $rules)->validate();
    }
}
