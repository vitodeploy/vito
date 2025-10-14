<?php

namespace App\WorkflowActions\Site;

class CreateLoadBalancerSite extends CreateSite
{
    public function inputs(): array
    {
        return array_merge(parent::inputs(), [
            'type' => 'load-balancer',
            'method' => 'Load balancing method in (round-robin/least-connections/ip-hash)',
        ]);
    }
}
