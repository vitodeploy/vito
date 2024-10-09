<?php

namespace Tests\Feature;

use App\Enums\FirewallRuleStatus;
use App\Facades\SSH;
use App\Models\FirewallRule;
use App\Web\Pages\Servers\Firewall\Index;
use App\Web\Pages\Servers\Firewall\Widgets\RulesList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FirewallTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_firewall_rule(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        Livewire::test(Index::class, [
            'server' => $this->server,
        ])
            ->callAction('create', [
                'type' => 'allow',
                'protocol' => 'tcp',
                'port' => '1234',
                'source' => '0.0.0.0',
                'mask' => '0',
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('firewall_rules', [
            'port' => '1234',
            'status' => FirewallRuleStatus::READY,
        ]);
    }

    public function test_see_firewall_rules(): void
    {
        $this->actingAs($this->user);

        $rule = FirewallRule::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->get(Index::getUrl(['server' => $this->server]))
            ->assertSuccessful()
            ->assertSee($rule->source)
            ->assertSee($rule->port);
    }

    public function test_delete_firewall_rule(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $rule = FirewallRule::factory()->create([
            'server_id' => $this->server->id,
        ]);

        Livewire::test(RulesList::class, [
            'server' => $this->server,
        ])
            ->callTableAction('delete', $rule->id)
            ->assertSuccessful();

        $this->assertDatabaseMissing('firewall_rules', [
            'id' => $rule->id,
        ]);
    }
}
