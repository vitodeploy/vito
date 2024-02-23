<?php

namespace Tests\Feature;

use App\Enums\FirewallRuleStatus;
use App\Http\Livewire\Firewall\CreateFirewallRule;
use App\Http\Livewire\Firewall\FirewallRulesList;
use App\Jobs\Firewall\AddToServer;
use App\Jobs\Firewall\RemoveFromServer;
use App\Models\FirewallRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Tests\TestCase;

class FirewallTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_firewall_rule(): void
    {
        Bus::fake();

        $this->actingAs($this->user);

        Livewire::test(CreateFirewallRule::class, ['server' => $this->server])
            ->set('type', 'allow')
            ->set('protocol', 'tcp')
            ->set('port', '1234')
            ->set('source', '0.0.0.0')
            ->set('mask', '0')
            ->call('create')
            ->assertSuccessful();

        Bus::assertDispatched(AddToServer::class);

        $this->assertDatabaseHas('firewall_rules', [
            'port' => '1234',
        ]);
    }

    public function test_see_firewall_rules(): void
    {
        $this->actingAs($this->user);

        $rule = FirewallRule::factory()->create([
            'server_id' => $this->server->id,
        ]);

        Livewire::test(FirewallRulesList::class, ['server' => $this->server])
            ->assertSee([
                $rule->source,
                $rule->port,
            ]);
    }

    public function test_delete_firewall_rule(): void
    {
        Bus::fake();

        $this->actingAs($this->user);

        $rule = FirewallRule::factory()->create([
            'server_id' => $this->server->id,
        ]);

        Livewire::test(FirewallRulesList::class, ['server' => $this->server])
            ->set('deleteId', $rule->id)
            ->call('delete')
            ->assertSuccessful();

        Bus::assertDispatched(RemoveFromServer::class);

        $this->assertDatabaseHas('firewall_rules', [
            'id' => $rule->id,
            'status' => FirewallRuleStatus::DELETING,
        ]);
    }
}
