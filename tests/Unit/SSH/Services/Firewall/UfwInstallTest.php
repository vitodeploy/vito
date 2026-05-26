<?php

namespace Tests\Unit\SSH\Services\Firewall;

use App\Facades\SSH;
use App\Services\Firewall\Ufw;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UfwInstallTest extends TestCase
{
    use RefreshDatabase;

    public function test_install_seeds_ssh_rule_with_custom_server_port(): void
    {
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
    }

    public function test_install_seeds_ssh_rule_with_default_port_when_unchanged(): void
    {
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
    }

    public function test_install_seeds_http_and_https_rules_unchanged(): void
    {
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
    }
}
