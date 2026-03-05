<?php

namespace Tests\Feature\Jobs;

use App\Enums\FirewallRuleStatus;
use App\Jobs\FirewallRule\ApplyRulesJob;
use App\Models\FirewallRule;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FirewallApplyRulesJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_apply_rules_job_failed_sets_non_ready_rules_to_failed_and_logs(): void
    {
        $creatingRule = FirewallRule::factory()->create([
            'server_id' => $this->server->id,
            'status' => FirewallRuleStatus::CREATING,
        ]);

        $readyRule = FirewallRule::factory()->create([
            'server_id' => $this->server->id,
            'status' => FirewallRuleStatus::READY,
        ]);

        $deletingRule = FirewallRule::factory()->create([
            'server_id' => $this->server->id,
            'status' => FirewallRuleStatus::DELETING,
        ]);

        $job = new ApplyRulesJob($creatingRule);
        $job->failed(new Exception('Firewall error'));

        $creatingRule->refresh();
        $readyRule->refresh();
        $deletingRule->refresh();

        $this->assertEquals(FirewallRuleStatus::FAILED, $creatingRule->status);
        $this->assertEquals(FirewallRuleStatus::READY, $readyRule->status);
        $this->assertEquals(FirewallRuleStatus::FAILED, $deletingRule->status);

        $this->assertDatabaseHas('server_logs', [
            'server_id' => $this->server->id,
            'type' => 'apply-firewall-rules-failed',
        ]);
    }
}
