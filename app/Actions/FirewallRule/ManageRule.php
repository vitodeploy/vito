<?php

namespace App\Actions\FirewallRule;

use App\Enums\FirewallRuleStatus;
use App\Jobs\FirewallRule\ApplyRulesJob;
use App\Models\FirewallRule;
use App\Models\Server;
use App\Support\Cidr;
use App\ValidationRules\PortOrPortRangeRule;
use Illuminate\Support\Facades\Validator;

class ManageRule
{
    /**
     * @param  array<string, mixed>  $input
     * @return FirewallRule $rule
     */
    public function create(Server $server, array $input): FirewallRule
    {
        $input = $this->normalizePort($input);
        $this->validate($input);

        $rule = new FirewallRule($this->attributesFromInput($input, FirewallRuleStatus::CREATING));
        $rule->server_id = $server->id;
        $rule->save();

        $this->queueApply($rule);

        return $rule;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return FirewallRule $rule
     */
    public function update(FirewallRule $rule, array $input): FirewallRule
    {
        $input = $this->normalizePort($input);
        $this->validate($input);

        $rule->update($this->attributesFromInput($input, FirewallRuleStatus::UPDATING));

        $this->queueApply($rule);

        return $rule;
    }

    public function delete(FirewallRule $rule): void
    {
        $rule->status = FirewallRuleStatus::DELETING;
        $rule->save();

        $this->queueApply($rule);
    }

    private function validate(array $input): void
    {
        $source = $input['source'] ?? null;
        $maxMask = is_string($source) && Cidr::isValidAddress($source) ? Cidr::bits($source) : 32;

        $rules = [
            'name' => [
                'required',
                'string',
                'max:18',
            ],
            'type' => [
                'required',
                'in:allow,deny',
            ],
            'protocol' => [
                'required',
                'in:tcp,udp',
            ],
            'port' => [
                'required',
                new PortOrPortRangeRule,
            ],
            'source' => [
                'nullable',
                'ip',
            ],
            'mask' => [
                'nullable',
                'numeric',
                'min:1',
                'max:'.$maxMask,
            ],
        ];

        if (isset($input['source_any']) && $input['source_any'] === false) {
            $rules['source'] = ['required', 'ip'];
            $rules['mask'] = ['required', 'numeric', 'min:1', 'max:'.$maxMask];
        }

        Validator::make($input, $rules)->validate();
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function normalizePort(array $input): array
    {
        $port = $input['port'] ?? null;
        $input['port'] = is_string($port) || is_int($port) ? (string) $port : null;

        return $input;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function attributesFromInput(array $input, FirewallRuleStatus $status): array
    {
        $sourceAny = $input['source_any'] ?? empty($input['source'] ?? null);

        return [
            'name' => $input['name'],
            'type' => $input['type'],
            'protocol' => $input['protocol'],
            'port' => $input['port'],
            'source' => $sourceAny ? null : $input['source'],
            'mask' => $sourceAny ? null : ($input['mask'] ?? null),
            'status' => $status,
        ];
    }

    private function queueApply(FirewallRule $rule): void
    {
        dispatch(new ApplyRulesJob($rule))->onQueue('ssh');
    }
}
