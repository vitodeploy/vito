<?php

namespace Tests\Feature\API;

use App\Enums\FirewallRuleStatus;
use App\Facades\SSH;
use App\Models\FirewallRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FirewallTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_firewall_rule(): void
    {
        SSH::fake();

        Sanctum::actingAs($this->user, ['read', 'write']);

        $this->json('POST', route('api.projects.servers.firewall-rules.create', [
            'project' => $this->server->project,
            'server' => $this->server,
        ]), [
            'type' => 'allow',
            'protocol' => 'tcp',
            'port' => '1234',
            'source' => '0.0.0.0',
            'mask' => '0',
        ])
            ->assertSuccessful()
            ->assertJsonFragment([
                'port' => 1234,
                'status' => FirewallRuleStatus::READY,
            ]);
    }

    public function test_see_firewall_rules(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var FirewallRule $rule */
        $rule = FirewallRule::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->json('GET', route('api.projects.servers.firewall-rules', [
            'project' => $this->server->project,
            'server' => $this->server,
        ]))
            ->assertSuccessful()
            ->assertJsonFragment([
                'source' => $rule->source,
                'port' => $rule->port,
            ]);
    }

    public function test_delete_firewall_rule(): void
    {
        SSH::fake();

        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var FirewallRule $rule */
        $rule = FirewallRule::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->json('DELETE', route('api.projects.servers.firewall-rules.delete', [
            'project' => $this->server->project,
            'server' => $this->server,
            'firewallRule' => $rule,
        ]))
            ->assertNoContent();
    }
}
