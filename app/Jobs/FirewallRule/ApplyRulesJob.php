<?php

namespace App\Jobs\FirewallRule;

use App\DTOs\SocketEventDTO;
use App\Enums\FirewallRuleStatus;
use App\Events\SocketEvent;
use App\Http\Resources\FirewallRuleResource;
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
                $projectId = $this->rule->server->project_id;
                $ruleId = $this->rule->id;
                $this->rule->delete();

                SocketEvent::dispatch(new SocketEventDTO(
                    projectId: $projectId,
                    type: 'firewall-rule.deleted',
                    data: ['id' => $ruleId],
                ));

                return;
            }

            $this->rule->status = FirewallRuleStatus::READY;
            $this->rule->save();
            $this->broadcastRuleUpdate();
        });
    }

    public function failed(Exception $e): void
    {
        $failedRules = $this->rule->server->firewallRules()
            ->where('status', '!=', FirewallRuleStatus::READY)
            ->get();

        $this->rule->server->firewallRules()
            ->where('status', '!=', FirewallRuleStatus::READY)
            ->update(['status' => FirewallRuleStatus::FAILED]);

        foreach ($failedRules as $rule) {
            $rule->status = FirewallRuleStatus::FAILED;
            SocketEvent::dispatch(new SocketEventDTO(
                projectId: $rule->server->project_id,
                type: 'firewall-rule.updated',
                data: new FirewallRuleResource($rule),
            ));
        }

        ServerLog::log($this->rule->server, 'apply-firewall-rules-failed', $e->getMessage());
    }

    private function broadcastRuleUpdate(): void
    {
        $this->rule->refresh();

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $this->rule->server->project_id,
            type: 'firewall-rule.updated',
            data: new FirewallRuleResource($this->rule),
        ));
    }
}
