<?php

namespace App\Actions\Network;

use App\DTOs\SocketEventDTO;
use App\Enums\FirewallRuleStatus;
use App\Events\SocketEvent;
use App\Http\Resources\NetworkFirewallRuleResource;
use App\Models\Network;
use App\Models\NetworkFirewallRule;
use App\ValidationRules\PortOrPortRangeRule;
use Illuminate\Support\Facades\Validator;

class ManageNetworkFirewallRule
{
    public function __construct(private ApplyNetworkFirewall $apply) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function create(Network $network, array $input): NetworkFirewallRule
    {
        $this->validate($input);

        $rule = $network->firewallRules()->create($this->attributes($input));

        $this->broadcast($network, 'network-firewall-rule.updated', new NetworkFirewallRuleResource($rule));
        $this->apply->handle($network);

        return $rule;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function update(NetworkFirewallRule $rule, array $input): NetworkFirewallRule
    {
        $this->validate($input);

        $rule->update($this->attributes($input));

        $this->broadcast($rule->network, 'network-firewall-rule.updated', new NetworkFirewallRuleResource($rule));
        $this->apply->handle($rule->network);

        return $rule;
    }

    public function delete(NetworkFirewallRule $rule): void
    {
        $network = $rule->network;
        $ruleId = $rule->id;
        $rule->delete();

        $this->broadcast($network, 'network-firewall-rule.deleted', ['id' => $ruleId]);
        $this->apply->handle($network);
    }

    /**
     * @param  array<string, mixed>|NetworkFirewallRuleResource  $data
     */
    private function broadcast(Network $network, string $type, array|NetworkFirewallRuleResource $data): void
    {
        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $network->project_id,
            type: $type,
            data: $data,
        ));
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function attributes(array $input): array
    {
        $port = $input['port'] ?? null;

        return [
            'name' => $input['name'],
            'protocol' => $input['protocol'] ?? null,
            'port' => ($port === null || $port === '') ? null : (string) $port,
            'status' => FirewallRuleStatus::READY,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function validate(array $input): void
    {
        $port = $input['port'] ?? null;
        $isRange = is_string($port) && str_contains($port, ':');

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'protocol' => [$isRange ? 'required' : 'nullable', 'in:tcp,udp'],
            'port' => ['nullable', new PortOrPortRangeRule],
        ], [
            'protocol.required' => __('A protocol is required when the port is a range, because ufw rejects a multi-port rule without one.'),
        ])->validate();
    }
}
