<?php

use App\Enums\FirewallRuleStatus;
use App\Jobs\FirewallRule\ApplyRulesJob;
use App\Models\FirewallRule;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('apply rules job failed sets non ready rules to failed and logs', function () {
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

    expect($creatingRule->status)->toEqual(FirewallRuleStatus::FAILED);
    expect($readyRule->status)->toEqual(FirewallRuleStatus::READY);
    expect($deletingRule->status)->toEqual(FirewallRuleStatus::FAILED);

    $this->assertDatabaseHas('server_logs', [
        'server_id' => $this->server->id,
        'type' => 'apply-firewall-rules-failed',
    ]);
});
