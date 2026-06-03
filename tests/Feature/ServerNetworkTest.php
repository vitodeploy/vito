<?php

namespace Tests\Feature;

use App\Enums\IpAddressStatus;
use App\Enums\IpAddressType;
use App\Facades\SSH;
use App\Models\ServerIpAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ServerNetworkTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_network_page(): void
    {
        $this->actingAs($this->user);

        ServerIpAddress::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->get(route('servers.network', $this->server))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('server-network/index'));
    }

    public function test_add_ip_address(): void
    {
        SSH::fake('[]');

        $this->actingAs($this->user);

        $this->post(route('servers.network.ips.store', ['server' => $this->server]), [
            'ip' => '203.0.113.10',
            'prefix_length' => '32',
            'interface' => 'eth0',
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('server_ip_addresses', [
            'server_id' => $this->server->id,
            'ip' => '203.0.113.10',
            'is_managed' => true,
            'status' => IpAddressStatus::CONFIGURED,
            'type' => IpAddressType::PUBLIC,
        ]);

        SSH::assertExecutedContains('netplan apply');
    }

    public function test_add_ipv4_without_prefix_defaults_to_32(): void
    {
        SSH::fake('[]');

        $this->actingAs($this->user);

        $this->post(route('servers.network.ips.store', ['server' => $this->server]), [
            'ip' => '203.0.113.20',
            'interface' => 'eth0',
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('server_ip_addresses', [
            'ip' => '203.0.113.20',
            'prefix_length' => 32,
        ]);
    }

    public function test_add_ipv6_without_prefix_defaults_to_64(): void
    {
        SSH::fake('[]');

        $this->actingAs($this->user);

        $this->post(route('servers.network.ips.store', ['server' => $this->server]), [
            'ip' => '2a01:4f9:c010:b550::2',
            'interface' => 'eth0',
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('server_ip_addresses', [
            'ip' => '2a01:4f9:c010:b550::2',
            'prefix_length' => 64,
        ]);
    }

    public function test_added_ip_is_merged_with_existing_static_addresses(): void
    {
        $this->server->update(['ip' => '203.0.113.10']);

        SSH::fake(json_encode([
            [
                'ifname' => 'eth0',
                'addr_info' => [
                    ['family' => 'inet', 'local' => '203.0.113.10', 'prefixlen' => 32, 'scope' => 'global', 'dynamic' => true],
                    ['family' => 'inet6', 'local' => '2a01:4f9:c010:b550::1', 'prefixlen' => 64, 'scope' => 'global'],
                ],
            ],
        ]) ?: '');

        $this->actingAs($this->user);

        $this->post(route('servers.network.ips.store', ['server' => $this->server]), [
            'ip' => '2a01:4f9:c010:b550::2',
            'interface' => 'eth0',
        ])->assertSessionDoesntHaveErrors();

        $content = SSH::getUploadedContent();

        $this->assertStringContainsString('2a01:4f9:c010:b550::2/64', $content);
        $this->assertStringContainsString('2a01:4f9:c010:b550::1/64', $content);
        $this->assertStringNotContainsString('203.0.113.10', $content);
    }

    public function test_add_ip_range_creates_a_row_per_address(): void
    {
        SSH::fake('[]');

        $this->actingAs($this->user);

        $this->post(route('servers.network.ips.store', ['server' => $this->server]), [
            'ip' => '203.0.113.10',
            'ip_last' => '203.0.113.13',
            'interface' => 'eth0',
        ])->assertSessionDoesntHaveErrors();

        foreach (['203.0.113.10', '203.0.113.11', '203.0.113.12', '203.0.113.13'] as $ip) {
            $this->assertDatabaseHas('server_ip_addresses', [
                'server_id' => $this->server->id,
                'ip' => $ip,
                'is_managed' => true,
            ]);
        }
    }

    public function test_add_ip_range_rejects_first_after_last(): void
    {
        $this->actingAs($this->user);

        $this->from(route('servers.network', $this->server))
            ->post(route('servers.network.ips.store', ['server' => $this->server]), [
                'ip' => '203.0.113.20',
                'ip_last' => '203.0.113.10',
                'interface' => 'eth0',
            ])
            ->assertSessionHasErrors(['ip_last']);
    }

    public function test_add_ip_range_rejects_oversized_range(): void
    {
        $this->actingAs($this->user);

        $this->from(route('servers.network', $this->server))
            ->post(route('servers.network.ips.store', ['server' => $this->server]), [
                'ip' => '10.0.0.0',
                'ip_last' => '10.0.5.0',
                'interface' => 'eth0',
            ])
            ->assertSessionHasErrors(['ip_last']);
    }

    public function test_add_ip_address_rejects_invalid_ip(): void
    {
        $this->actingAs($this->user);

        $this->from(route('servers.network', $this->server))
            ->post(route('servers.network.ips.store', ['server' => $this->server]), [
                'ip' => 'not-an-ip',
                'prefix_length' => '32',
                'interface' => 'eth0',
            ])
            ->assertSessionHasErrors(['ip']);
    }

    public function test_add_ipv4_rejects_prefix_over_32(): void
    {
        $this->actingAs($this->user);

        $this->from(route('servers.network', $this->server))
            ->post(route('servers.network.ips.store', ['server' => $this->server]), [
                'ip' => '203.0.113.10',
                'prefix_length' => '40',
                'interface' => 'eth0',
            ])
            ->assertSessionHasErrors(['prefix_length']);
    }

    public function test_delete_managed_ip_address(): void
    {
        SSH::fake('[]');

        $this->actingAs($this->user);

        $address = ServerIpAddress::factory()->create([
            'server_id' => $this->server->id,
            'is_managed' => true,
        ]);

        $this->delete(route('servers.network.ips.destroy', [
            'server' => $this->server,
            'serverIpAddress' => $address,
        ]))->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('server_ip_addresses', [
            'id' => $address->id,
        ]);
    }

    public function test_set_ip_as_primary_updates_server_ip(): void
    {
        $this->actingAs($this->user);

        $address = ServerIpAddress::factory()->create([
            'server_id' => $this->server->id,
            'ip' => '203.0.113.50',
            'type' => IpAddressType::PUBLIC,
            'is_primary' => false,
        ]);

        $this->post(route('servers.network.ips.primary', [
            'server' => $this->server,
            'serverIpAddress' => $address,
        ]))->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('servers', [
            'id' => $this->server->id,
            'ip' => '203.0.113.50',
        ]);

        $this->assertDatabaseHas('server_ip_addresses', [
            'id' => $address->id,
            'is_primary' => true,
        ]);
    }

    public function test_cannot_delete_discovered_ip_address(): void
    {
        $this->actingAs($this->user);

        $address = ServerIpAddress::factory()->create([
            'server_id' => $this->server->id,
            'is_managed' => false,
        ]);

        $this->from(route('servers.network', $this->server))
            ->delete(route('servers.network.ips.destroy', [
                'server' => $this->server,
                'serverIpAddress' => $address,
            ]))
            ->assertSessionHasErrors(['ip']);

        $this->assertDatabaseHas('server_ip_addresses', [
            'id' => $address->id,
        ]);
    }

    public function test_refresh_pulls_ips_from_server(): void
    {
        $this->server->update(['ip' => '203.0.113.10']);

        SSH::fake($this->ipAddrJson());

        $this->actingAs($this->user);

        $this->post(route('servers.network.refresh', ['server' => $this->server]))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('server_ip_addresses', [
            'server_id' => $this->server->id,
            'ip' => '203.0.113.10',
            'interface' => 'eth0',
            'is_managed' => false,
            'is_primary' => true,
            'type' => IpAddressType::PUBLIC,
        ]);

        $this->assertDatabaseHas('server_ip_addresses', [
            'server_id' => $this->server->id,
            'ip' => '10.0.0.5',
            'type' => IpAddressType::PRIVATE,
        ]);

        $this->assertDatabaseMissing('server_ip_addresses', [
            'server_id' => $this->server->id,
            'ip' => '127.0.0.1',
        ]);

        $this->assertDatabaseMissing('server_ip_addresses', [
            'server_id' => $this->server->id,
            'ip' => 'fe80::1',
        ]);
    }

    public function test_refresh_resyncs_managed_row_fields_but_not_status(): void
    {
        $address = ServerIpAddress::factory()->create([
            'server_id' => $this->server->id,
            'ip' => '203.0.113.77',
            'interface' => 'old0',
            'prefix_length' => 24,
            'status' => IpAddressStatus::CONFIGURING,
            'is_managed' => true,
        ]);

        SSH::fake(json_encode([
            [
                'ifname' => 'eth0',
                'addr_info' => [
                    ['family' => 'inet', 'local' => '203.0.113.77', 'prefixlen' => 32, 'scope' => 'global'],
                ],
            ],
        ]) ?: '');

        $this->actingAs($this->user);

        $this->post(route('servers.network.refresh', ['server' => $this->server]))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('server_ip_addresses', [
            'id' => $address->id,
            'interface' => 'eth0',
            'prefix_length' => 32,
            'is_managed' => true,
            'status' => IpAddressStatus::CONFIGURING,
        ]);
    }

    public function test_refresh_marks_vito_managed_ips_from_marker(): void
    {
        $output = (json_encode([
            [
                'ifname' => 'eth0',
                'addr_info' => [
                    ['family' => 'inet', 'local' => '203.0.113.50', 'prefixlen' => 32, 'scope' => 'global'],
                    ['family' => 'inet', 'local' => '203.0.113.99', 'prefixlen' => 32, 'scope' => 'global'],
                ],
            ],
        ]) ?: '')."\n===VITO-MANAGED===\n# vito-managed: 203.0.113.50\n";

        SSH::fake($output);

        $this->actingAs($this->user);

        $this->post(route('servers.network.refresh', ['server' => $this->server]))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('server_ip_addresses', [
            'server_id' => $this->server->id,
            'ip' => '203.0.113.50',
            'is_managed' => true,
        ]);

        $this->assertDatabaseHas('server_ip_addresses', [
            'server_id' => $this->server->id,
            'ip' => '203.0.113.99',
            'is_managed' => false,
        ]);
    }

    private function ipAddrJson(): string
    {
        return json_encode([
            [
                'ifname' => 'lo',
                'addr_info' => [
                    ['family' => 'inet', 'local' => '127.0.0.1', 'prefixlen' => 8, 'scope' => 'host'],
                ],
            ],
            [
                'ifname' => 'eth0',
                'addr_info' => [
                    ['family' => 'inet', 'local' => '203.0.113.10', 'prefixlen' => 32, 'scope' => 'global'],
                    ['family' => 'inet', 'local' => '10.0.0.5', 'prefixlen' => 24, 'scope' => 'global'],
                    ['family' => 'inet6', 'local' => 'fe80::1', 'prefixlen' => 64, 'scope' => 'link'],
                ],
            ],
        ]) ?: '';
    }
}
