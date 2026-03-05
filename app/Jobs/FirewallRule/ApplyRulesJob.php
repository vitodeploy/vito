<?php

namespace App\Jobs\FirewallRule;

use App\Enums\FirewallRuleStatus;
use App\Models\FirewallRule;
use App\Models\ServerLog;
use App\Models\Service;
use App\Services\Firewall\Firewall;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ApplyRulesJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(protected FirewallRule $rule) {}

    public function handle(): void
    {
        $this->run("server-{$this->rule->server_id}", function () {
            /** @var Service $service */
            $service = $this->rule->server->firewall();
            /** @var Firewall $handler */
            $handler = $service->handler();
            $handler->applyRules();

            if ($this->rule->status === FirewallRuleStatus::DELETING) {
                $this->rule->delete();

                return;
            }

            $this->rule->status = FirewallRuleStatus::READY;
            $this->rule->save();
        });
    }

    public function failed(Exception $e): void
    {
        $this->rule->server->firewallRules()
            ->where('status', '!=', FirewallRuleStatus::READY)
            ->update(['status' => FirewallRuleStatus::FAILED]);

        ServerLog::log($this->rule->server, 'apply-firewall-rules-failed', $e->getMessage());
    }
}
