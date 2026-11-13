<?php

namespace App\Jobs\FirewallRule;

use App\DTOs\SocketEventDTO;
use App\Enums\FirewallRuleStatus;
use App\Events\SocketEvent;
use App\Http\Resources\FirewallRuleResource;
use App\Models\FirewallRule;
use App\Models\Server;
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

    /**
     * @param  array<int, int>  $ruleIds  rules whose pending status is settled after the apply; ids are carried instead of models so the job survives a rule being deleted while queued
     */
    public function __construct(protected Server $server, protected array $ruleIds) {}

    public function handle(): void
    {
        $this->run("server-{$this->server->id}", function () {
            /** @var Service $service */
            $service = $this->server->firewall();
            /** @var Firewall $handler */
            $handler = $service->handler();
            $handler->applyRules();

            foreach ($this->server->firewallRules()->whereKey($this->ruleIds)->get() as $rule) {
                if ($rule->status === FirewallRuleStatus::DELETING) {
                    $rule->delete();

                    SocketEvent::dispatch(new SocketEventDTO(
                        projectId: $this->server->project_id,
                        type: 'firewall-rule.deleted',
                        data: ['id' => $rule->id],
                    ));

                    continue;
                }

                $updated = FirewallRule::query()
                    ->whereKey($rule->id)
                    ->whereIn('status', [FirewallRuleStatus::CREATING, FirewallRuleStatus::UPDATING])
                    ->update(['status' => FirewallRuleStatus::READY]);

                if ($updated > 0) {
                    $this->broadcastRuleUpdate($rule);
                }
            }
        });
    }

    public function failed(Exception $e): void
    {
        $failedRules = $this->server->firewallRules()
            ->where('component_managed', false)
            ->where('status', '!=', FirewallRuleStatus::READY)
            ->get();

        $this->server->firewallRules()
            ->where('component_managed', false)
            ->where('status', '!=', FirewallRuleStatus::READY)
            ->update(['status' => FirewallRuleStatus::FAILED]);

        foreach ($failedRules as $rule) {
            $rule->status = FirewallRuleStatus::FAILED;
            SocketEvent::dispatch(new SocketEventDTO(
                projectId: $this->server->project_id,
                type: 'firewall-rule.updated',
                data: new FirewallRuleResource($rule),
            ));
        }

        ServerLog::log($this->server, 'apply-firewall-rules-failed', $e->getMessage());
    }

    private function broadcastRuleUpdate(FirewallRule $rule): void
    {
        $rule->refresh();

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $rule->server->project_id,
            type: 'firewall-rule.updated',
            data: new FirewallRuleResource($rule),
        ));
    }
}
