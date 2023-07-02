<?php

namespace App\Jobs\Firewall;

use App\Enums\FirewallRuleStatus;
use App\Events\Broadcast;
use App\Jobs\Job;
use App\Models\FirewallRule;

class RemoveFromServer extends Job
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
            ->removeRule(
                $this->firewallRule->type,
                $this->firewallRule->real_protocol,
                $this->firewallRule->port,
                $this->firewallRule->source,
                $this->firewallRule->mask
            );
        $this->firewallRule->delete();
        event(
            new Broadcast('delete-firewall-rule-finished', [
                'id' => $this->firewallRule->id,
            ])
        );
    }

    public function failed(): void
    {
        $this->firewallRule->status = FirewallRuleStatus::READY;
        $this->firewallRule->save();
        event(
            new Broadcast('delete-firewall-rule-failed', [
                'firewallRule' => $this->firewallRule,
            ])
        );
    }
}
