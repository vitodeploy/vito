<?php

namespace App\Services\Firewall;

use App\Enums\FirewallRuleStatus;
use App\Services\AbstractService;

abstract class AbstractFirewall extends AbstractService implements Firewall
{
    public function deletionRules(): array
    {
        return [
            'service' => [
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->service->server->componentsUsing($this->service)->isNotEmpty()
                        || \App\Models\EnvironmentAttachment::query()->where('server_id', $this->service->server_id)->exists()
                        || $this->service->server->firewallRules()->where('component_managed', true)->exists()) {
                        $fail('Remove load balancer backends and environment resources and finish their firewall cleanup before stopping or removing the firewall.');
                    }
                },
            ],
        ];
    }

    protected function createBasicFirewallRules(): void
    {
        $this->service->server->firewallRules()->createMany([
            [
                'type' => 'allow',
                'name' => 'SSH',
                'protocol' => 'tcp',
                'port' => $this->service->server->port ?? 22,
                'source' => null,
                'mask' => null,
                'status' => FirewallRuleStatus::READY,
            ],
            [
                'type' => 'allow',
                'name' => 'HTTP',
                'protocol' => 'tcp',
                'port' => '80',
                'source' => null,
                'mask' => null,
                'status' => FirewallRuleStatus::READY,
            ],
            [
                'type' => 'allow',
                'name' => 'HTTPS',
                'protocol' => 'tcp',
                'port' => '443',
                'source' => null,
                'mask' => null,
                'status' => FirewallRuleStatus::READY,
            ],
        ]);
        info('rules created');
    }
}
