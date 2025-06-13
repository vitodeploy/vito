<?php

namespace App\Actions\FirewallRule;

use App\Enums\FirewallRuleStatus;
use App\Models\FirewallRule;
use App\Models\Server;
use App\Models\Service;
use App\Services\Firewall\Firewall;
use Exception;
use Illuminate\Support\Facades\Validator;

class ManageRule
{
    /**
     * @param  array<string, mixed>  $input
     * @return FirewallRule $rule
     */
    public function create(Server $server, array $input): FirewallRule
    {
        Validator::make($input, self::rules($input))->validate();

        $sourceAny = $input['source_any'] ?? empty($input['source'] ?? null);
        $rule = new FirewallRule([
            'name' => $input['name'],
            'server_id' => $server->id,
            'type' => $input['type'],
            'protocol' => $input['protocol'],
            'port' => $input['port'],
            'source' => $sourceAny ? null : $input['source'],
            'mask' => $sourceAny ? null : ($input['mask'] ?? null),
            'status' => FirewallRuleStatus::CREATING,
        ]);

        $rule->save();

        dispatch(fn () => $this->applyRule($rule));

        return $rule;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return FirewallRule $rule
     */
    public function update(FirewallRule $rule, array $input): FirewallRule
    {
        Validator::make($input, self::rules($input))->validate();

        $sourceAny = $input['source_any'] ?? empty($input['source'] ?? null);
        $rule->update([
            'name' => $input['name'],
            'type' => $input['type'],
            'protocol' => $input['protocol'],
            'port' => $input['port'],
            'source' => $sourceAny ? null : $input['source'],
            'mask' => $sourceAny ? null : ($input['mask'] ?? null),
            'status' => FirewallRuleStatus::UPDATING,
        ]);

        dispatch(fn () => $this->applyRule($rule));

        return $rule;
    }

    public function delete(FirewallRule $rule): void
    {
        $rule->status = FirewallRuleStatus::DELETING;
        $rule->save();

        dispatch(fn () => $this->applyRule($rule));
    }

    protected function applyRule(FirewallRule $rule): void
    {
        try {
            /** @var Service $service */
            $service = $rule->server->firewall();
            /** @var Firewall $handler */
            $handler = $service->handler();
            $handler->applyRules();
        } catch (Exception) {
            $rule->server->firewallRules()
                ->where('status', '!=', FirewallRuleStatus::READY)
                ->update(['status' => FirewallRuleStatus::FAILED]);

            return;
        }

        if ($rule->status === FirewallRuleStatus::DELETING) {
            $rule->delete();

            return;
        }

        $rule->status = FirewallRuleStatus::READY;
        $rule->save();
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, array<string>>
     */
    public static function rules(array $input): array
    {
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
                'numeric',
                'min:1',
                'max:65535',
            ],
            'source' => [
                'nullable',
                'ip',
            ],
            'mask' => [
                'nullable',
                'numeric',
                'min:1',
                'max:32',
            ],
        ];

        if (isset($input['source_any']) && $input['source_any'] === false) {
            $rules['source'] = ['required', 'ip'];
            $rules['mask'] = ['required', 'numeric', 'min:1', 'max:32'];
        }

        return $rules;
    }
}
