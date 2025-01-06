<?php

namespace App\Actions\FirewallRule;

use App\Enums\FirewallRuleStatus;
use App\Models\FirewallRule;
use App\Models\Server;
use App\SSH\Services\Firewall\Firewall;
use Illuminate\Validation\Rule;

class CreateRule
{
    public function create(Server $server, array $input): FirewallRule
    {
        $rule = new FirewallRule([
            'server_id' => $server->id,
            'type' => $input['type'],
            'protocol' => $input['protocol'],
            'port' => $input['port'],
            'source' => $input['source'],
            'mask' => $input['mask'] ?? null,
        ]);

        /** @var Firewall $firewallHandler */
        $firewallHandler = $server->firewall()->handler();
        $firewallHandler->addRule(
            $rule->type,
            $rule->getRealProtocol(),
            $rule->port,
            $rule->source,
            $rule->mask
        );

        $rule->status = FirewallRuleStatus::READY;
        $rule->save();

        return $rule;
    }

    public static function rules(): array
    {
        return [
            'type' => [
                'required',
                'in:allow,deny',
            ],
            'protocol' => [
                'required',
                Rule::in(array_keys(config('core.firewall_protocols_port'))),
            ],
            'port' => [
                'required',
                'numeric',
                'min:1',
                'max:65535',
            ],
            'source' => [
                'required',
                'ip',
            ],
            'mask' => [
                'nullable',
                'numeric',
            ],
        ];
    }
}
