<?php

use App\Enums\FirewallRuleStatus;
use App\Facades\SSH;
use App\Models\FirewallRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('create firewall rule', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $this->post(route('firewall.store', ['server' => $this->server]), [
        'name' => 'Test',
        'type' => 'allow',
        'protocol' => 'tcp',
        'port' => '1234',
        'source' => '0.0.0.0',
        'mask' => '1',
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('firewall_rules', [
        'port' => '1234',
        'status' => FirewallRuleStatus::READY,
    ]);
});

test('see firewall rules', function () {
    $this->actingAs($this->user);

    FirewallRule::factory()->create([
        'server_id' => $this->server->id,
    ]);

    $this->get(route('firewall', $this->server))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('firewall/index'));
});

test('create firewall rule with port range', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $this->post(route('firewall.store', ['server' => $this->server]), [
        'name' => 'Range',
        'type' => 'allow',
        'protocol' => 'tcp',
        'port' => '3000:3010',
        'source' => null,
        'mask' => null,
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('firewall_rules', [
        'port' => '3000:3010',
        'status' => FirewallRuleStatus::READY,
    ]);

    SSH::assertExecutedContains('port 3000:3010');
});

test('port validation accepts max span', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $this->post(route('firewall.store', ['server' => $this->server]), [
        'name' => 'MaxSpan',
        'type' => 'allow',
        'protocol' => 'tcp',
        'port' => '1:65535',
        'source' => null,
        'mask' => null,
    ])->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('firewall_rules', [
        'port' => '1:65535',
    ]);
});

test('port validation rejects invalid input', function (string $port) {
    $this->actingAs($this->user);

    $this->from(route('firewall', $this->server))
        ->post(route('firewall.store', ['server' => $this->server]), [
            'name' => 'Invalid',
            'type' => 'allow',
            'protocol' => 'tcp',
            'port' => $port,
            'source' => null,
            'mask' => null,
        ])
        ->assertSessionHasErrors(['port']);
})->with('invalidPortProvider');

/**
 * @return array<string, array<int, string>>
 */
dataset('invalidPortProvider', function () {
    return [
        'zero' => ['0'],
        'above max' => ['65536'],
        'both above max' => ['100000:200000'],
        'reversed range' => ['5000:4000'],
        'equal range' => ['3000:3000'],
        'non-numeric' => ['abc'],
        'trailing colon' => ['3000:'],
        'leading zero' => ['01234'],
        'empty' => [''],
    ];
});

test('existing single port rule can be edited', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $rule = FirewallRule::factory()->create([
        'server_id' => $this->server->id,
        'port' => '22',
    ]);

    $this->put(route('firewall.update', [
        'server' => $this->server,
        'firewallRule' => $rule,
    ]), [
        'name' => 'SSH',
        'type' => 'allow',
        'protocol' => 'tcp',
        'port' => '2222',
        'source' => null,
        'mask' => null,
    ])->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('firewall_rules', [
        'id' => $rule->id,
        'port' => '2222',
    ]);
});

test('delete firewall rule', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $rule = FirewallRule::factory()->create([
        'server_id' => $this->server->id,
    ]);

    $this->delete(route('firewall.destroy', [
        'server' => $this->server,
        'firewallRule' => $rule,
    ]))->assertSessionDoesntHaveErrors();

    $this->assertDatabaseMissing('firewall_rules', [
        'id' => $rule->id,
    ]);
});
