<?php

namespace App\Actions\FirewallRule;

use App\Enums\FirewallRuleStatus;
use App\Models\FirewallRule;
use App\Models\Server;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CreateRule
{
    public function create(Server $server, array $input): FirewallRule
    {
        $this->validate($server, $input);

        $rule = new FirewallRule([
            'server_id' => $server->id,
            'type' => $input['type'],
            'protocol' => $input['protocol'],
            'port' => $input['port'],
            'source' => $input['source'],
            'mask' => $input['mask'] ?? null,
        ]);

        $server->firewall()
            ->handler()
            ->addRule(
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

    /**
     * @throws ValidationException
     */
    private function validate(Server $server, array $input): void
    {
        Validator::make($input, [
            'type' => [
                'required',
                'in:allow,deny',
            ],
            'protocol' => [
                'required',
                'in:'.implode(',', array_keys(config('core.firewall_protocols_port'))),
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
                'numeric',
            ],
        ])->validate();
    }
}
