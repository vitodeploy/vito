<?php

use App\Enums\FirewallRuleStatus;
use App\Facades\SSH;
use App\Models\FirewallRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('create firewall rule', function () {
    SSH::fake();

    Sanctum::actingAs($this->user, ['read', 'write']);

    $this->json('POST', route('api.projects.servers.firewall-rules.create', [
        'project' => $this->server->project,
        'server' => $this->server,
    ]), [
        'name' => 'Test',
        'type' => 'allow',
        'protocol' => 'tcp',
        'port' => '1234',
        'source' => '0.0.0.0',
        'mask' => '1',
    ])
        ->assertSuccessful()
        ->assertJsonFragment([
            'port' => '1234',
            'status' => FirewallRuleStatus::CREATING,
        ]);
});

test('create firewall rule with integer port input', function () {
    SSH::fake();

    Sanctum::actingAs($this->user, ['read', 'write']);

    $this->json('POST', route('api.projects.servers.firewall-rules.create', [
        'project' => $this->server->project,
        'server' => $this->server,
    ]), [
        'name' => 'IntPort',
        'type' => 'allow',
        'protocol' => 'tcp',
        'port' => 1234,
        'source' => null,
        'mask' => null,
    ])
        ->assertSuccessful()
        ->assertJsonFragment([
            'port' => '1234',
        ]);
});

test('create firewall rule with port range', function () {
    SSH::fake();

    Sanctum::actingAs($this->user, ['read', 'write']);

    $this->json('POST', route('api.projects.servers.firewall-rules.create', [
        'project' => $this->server->project,
        'server' => $this->server,
    ]), [
        'name' => 'RangeAPI',
        'type' => 'allow',
        'protocol' => 'tcp',
        'port' => '3000:3010',
        'source' => null,
        'mask' => null,
    ])
        ->assertSuccessful()
        ->assertJsonFragment([
            'port' => '3000:3010',
        ]);
});

test('edit firewall rule', function () {
    SSH::fake();

    Sanctum::actingAs($this->user, ['read', 'write']);

    $rule = FirewallRule::factory()->create([
        'server_id' => $this->server->id,
        'port' => '1234',
    ]);

    $this->json('PUT', route('api.projects.servers.firewall-rules.edit', [
        'project' => $this->server->project,
        'server' => $this->server,
        'firewallRule' => $rule,
    ]), [
        'name' => 'Test',
        'type' => 'allow',
        'protocol' => 'tcp',
        'port' => '55',
        'source' => null,
        'mask' => null,
    ])
        ->assertSuccessful()
        ->assertJsonFragment([
            'port' => '55',
            'status' => FirewallRuleStatus::UPDATING,
        ]);
});

test('see firewall rules', function () {
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
});

test('delete firewall rule', function () {
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
});

test('rejects non string or int port input', function (mixed $port) {
    SSH::fake();

    Sanctum::actingAs($this->user, ['read', 'write']);

    $this->json('POST', route('api.projects.servers.firewall-rules.create', [
        'project' => $this->server->project,
        'server' => $this->server,
    ]), [
        'name' => 'BadType',
        'type' => 'allow',
        'protocol' => 'tcp',
        'port' => $port,
        'source' => null,
        'mask' => null,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['port']);
})->with('nonScalarPortProvider');

/**
 * @return array<string, array<int, mixed>>
 */
dataset('nonScalarPortProvider', function () {
    return [
        'true boolean' => [true],
        'false boolean' => [false],
        'float' => [22.5],
        'array' => [[22]],
        'null' => [null],
    ];
});

test('rejects invalid port input', function (string $port) {
    SSH::fake();

    Sanctum::actingAs($this->user, ['read', 'write']);

    $this->json('POST', route('api.projects.servers.firewall-rules.create', [
        'project' => $this->server->project,
        'server' => $this->server,
    ]), [
        'name' => 'BadPort',
        'type' => 'allow',
        'protocol' => 'tcp',
        'port' => $port,
        'source' => null,
        'mask' => null,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['port']);
})->with('invalidPortProvider');

/**
 * @return array<string, array<int, string>>
 */
dataset('invalidPortProvider', function () {
    return [
        'zero' => ['0'],
        'above max' => ['65536'],
        'far above max' => ['99999'],
        'both above max' => ['100000:200000'],
        'second side above max' => ['1000:65536'],
        'reversed range' => ['5000:4000'],
        'equal range' => ['3000:3000'],
        'non-numeric' => ['abc'],
        'trailing colon' => ['3000:'],
        'leading colon' => [':3010'],
        'leading zero' => ['01234'],
        'negative' => ['-22'],
        'decimal' => ['22.5'],
        'whitespace inside' => ['3000 : 3010'],
        'three parts' => ['1000:2000:3000'],
        'empty' => [''],
    ];
});
