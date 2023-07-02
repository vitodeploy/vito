<?php

namespace App\Jobs\Firewall;

use App\Enums\FirewallRuleStatus;
use App\Events\Broadcast;
use App\Jobs\Job;
use App\Models\FirewallRule;

class AddToServer extends Job
{
    protected FirewallRule $firewallRule;

    public function __construct(FirewallRule $firewallRule)
    {
        $this->firewallRule = $firewallRule;
    }

    public function handle(): void
    {
        $this->firewallRule->server->firewall()
            ->handler()
            ->addRule(
                $this->firewallRule->type,
                $this->firewallRule->real_protocol,
                $this->firewallRule->port,
                $this->firewallRule->source,
                $this->firewallRule->mask
            );
        $this->firewallRule->status = FirewallRuleStatus::READY;
        $this->firewallRule->save();
        event(
            new Broadcast('create-firewall-rule-finished', [
                'firewallRule' => $this->firewallRule,
            ])
        );
    }

    public function failed(): void
    {
        $this->firewallRule->delete();
        event(
            new Broadcast('create-firewall-rule-failed', [
                'firewallRule' => $this->firewallRule,
            ])
        );
    }
}
