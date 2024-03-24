<?php

namespace App\Actions\FirewallRule;

use App\Enums\FirewallRuleStatus;
use App\Models\FirewallRule;
use App\Models\Server;

class DeleteRule
{
    public function delete(Server $server, FirewallRule $rule): void
    {
        $rule->status = FirewallRuleStatus::DELETING;
        $rule->save();

        $server->firewall()
            ->handler()
            ->removeRule(
                $rule->type,
                $rule->getRealProtocol(),
                $rule->port,
                $rule->source,
                $rule->mask
            );

        $rule->delete();
    }
}
