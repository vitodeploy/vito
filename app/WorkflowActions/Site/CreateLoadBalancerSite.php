<?php

namespace App\WorkflowActions\Site;

use App\DTOs\DynamicField;
use App\SiteTypes\LoadBalancer;

class CreateLoadBalancerSite extends CreateSite
{
    protected function siteType(): string
    {
        return LoadBalancer::id();
    }

    public function inputs(): array
    {
        return [
            ...parent::inputs(),
            DynamicField::make('method')->select()->label('Method')->options(['round-robin', 'least-connections', 'ip-hash'])->default('round-robin'),
        ];
    }
}
