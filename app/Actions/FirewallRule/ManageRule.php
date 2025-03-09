<?php

namespace App\Actions\FirewallRule;

use App\Enums\FirewallRuleStatus;
use App\Models\FirewallRule;
use App\Models\Server;
use App\SSH\Services\Firewall\Firewall;

class ManageRule
{
    /**
     * @param  array<string, mixed>  $input
     * @return FirewallRule $rule
     */
    public function create(Server $server, array $input): FirewallRule
    {
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
            /** @var Firewall $handler */
            $handler = $rule->server->firewall()->handler();
            $handler->applyRules();
        } catch (\Exception $e) {
            $rule->server->firewallRules()
                ->where('status', '!=', FirewallRuleStatus::READY)
                ->update(['status' => FirewallRuleStatus::FAILED]);

            throw $e;
        }

        if ($rule->status === FirewallRuleStatus::DELETING) {
            $rule->delete();

            return;
        }

        $rule->status = FirewallRuleStatus::READY;
        $rule->save();
    }

    /**
     * @return array<string, array<string>>
     */
    public static function rules(): array
    {
        return [
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
    }
}
