<?php

use App\Facades\SSH;
use App\Services\Firewall\Ufw;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('install seeds ssh rule with custom server port', function () {
    SSH::fake();

    $this->server->update(['port' => 22022]);

    /** @var Ufw $ufw */
    $ufw = $this->server->services()
        ->where('type', Ufw::type())
        ->firstOrFail()
        ->handler();

    $ufw->install();

    $this->assertDatabaseHas('firewall_rules', [
        'server_id' => $this->server->id,
        'name' => 'SSH',
        'port' => 22022,
    ]);

    $this->assertDatabaseMissing('firewall_rules', [
        'server_id' => $this->server->id,
        'name' => 'SSH',
        'port' => 22,
    ]);
});

test('install seeds ssh rule with default port when unchanged', function () {
    SSH::fake();

    $this->server->update(['port' => 22]);

    /** @var Ufw $ufw */
    $ufw = $this->server->services()
        ->where('type', Ufw::type())
        ->firstOrFail()
        ->handler();

    $ufw->install();

    $this->assertDatabaseHas('firewall_rules', [
        'server_id' => $this->server->id,
        'name' => 'SSH',
        'port' => 22,
    ]);
});

test('install seeds http and https rules unchanged', function () {
    SSH::fake();

    $this->server->update(['port' => 22022]);

    /** @var Ufw $ufw */
    $ufw = $this->server->services()
        ->where('type', Ufw::type())
        ->firstOrFail()
        ->handler();

    $ufw->install();

    $this->assertDatabaseHas('firewall_rules', [
        'server_id' => $this->server->id,
        'name' => 'HTTP',
        'port' => 80,
    ]);
    $this->assertDatabaseHas('firewall_rules', [
        'server_id' => $this->server->id,
        'name' => 'HTTPS',
        'port' => 443,
    ]);
});
