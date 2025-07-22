<?php

namespace App\Services\Firewall;

use App\Enums\FirewallRuleStatus;
use App\Services\AbstractService;

abstract class AbstractFirewall extends AbstractService implements Firewall
{
    protected function createBasicFirewallRules(): void
    {
        $this->service->server->firewallRules()->createMany([
            [
                'type' => 'allow',
                'name' => 'SSH',
                'protocol' => 'tcp',
                'port' => 22,
                'source' => null,
                'mask' => null,
                'status' => FirewallRuleStatus::READY,
            ],
            [
                'type' => 'allow',
                'name' => 'HTTP',
                'protocol' => 'tcp',
                'port' => 80,
                'source' => null,
                'mask' => null,
                'status' => FirewallRuleStatus::READY,
            ],
            [
                'type' => 'allow',
                'name' => 'HTTPS',
                'protocol' => 'tcp',
                'port' => 443,
                'source' => null,
                'mask' => null,
                'status' => FirewallRuleStatus::READY,
            ],
        ]);
        info('rules created');
    }
}
