<?php

namespace Tests\Unit\Commands;

use App\Enums\FirewallRuleStatus;
use App\Enums\OperatingSystem;
use App\Enums\ServerStatus;
use App\Enums\ServiceStatus;
use App\Enums\UserRole;
use App\Models\Project;
use App\Models\Server;
use App\Models\Service;
use App\Models\User;
use App\Models\UserProject;
use App\ServerProviders\Custom;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateLocalServerCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_local_server_with_minimal_options(): void
    {
        $this->artisan('servers:create-local', [
            'ip' => '192.168.1.100',
        ])
            ->expectsOutput("Creating local server 'local-server' with IP 192.168.1.100...")
            ->expectsOutput('Local server created successfully!')
            ->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseHas('servers', [
            'ip' => '192.168.1.100',
            'name' => 'local-server',
            'is_local' => true,
            'status' => ServerStatus::READY,
            'progress' => 100,
            'provider' => Custom::id(),
        ]);

        $server = Server::query()->where('ip', '192.168.1.100')->first();
        $this->assertTrue($server->is_local);
        $this->assertEquals(ServerStatus::READY, $server->status);
        $this->assertEquals(100, $server->progress);
    }

    public function test_create_local_server_with_custom_name(): void
    {
        $this->artisan('servers:create-local', [
            'ip' => '192.168.1.101',
            '--name' => 'my-custom-server',
        ])
            ->expectsOutput("Creating local server 'my-custom-server' with IP 192.168.1.101...")
            ->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseHas('servers', [
            'ip' => '192.168.1.101',
            'name' => 'my-custom-server',
            'is_local' => true,
        ]);
    }

    public function test_create_local_server_with_nginx(): void
    {
        $this->artisan('servers:create-local', [
            'ip' => '192.168.1.102',
            '--nginx' => 'Y',
        ])
            ->expectsOutput('Nginx service created.')
            ->assertExitCode(Command::SUCCESS);

        $server = Server::query()->where('ip', '192.168.1.102')->first();

        $this->assertDatabaseHas('services', [
            'server_id' => $server->id,
            'type' => 'webserver',
            'name' => 'nginx',
            'version' => 'latest',
            'status' => ServiceStatus::READY,
            'is_default' => true,
        ]);
    }

    public function test_create_local_server_with_nginx_lowercase(): void
    {
        $this->artisan('servers:create-local', [
            'ip' => '192.168.1.103',
            '--nginx' => 'y',
        ])
            ->expectsOutput('Nginx service created.')
            ->assertExitCode(Command::SUCCESS);

        $server = Server::query()->where('ip', '192.168.1.103')->first();
        $this->assertCount(1, $server->services()->where('name', 'nginx')->get());
    }

    public function test_create_local_server_without_nginx(): void
    {
        $this->artisan('servers:create-local', [
            'ip' => '192.168.1.104',
            '--nginx' => 'N',
        ])
            ->assertExitCode(Command::SUCCESS);

        $server = Server::query()->where('ip', '192.168.1.104')->first();
        $this->assertCount(0, $server->services()->where('name', 'nginx')->get());
    }

    public function test_create_local_server_with_firewall_rules(): void
    {
        $this->artisan('servers:create-local', [
            'ip' => '192.168.1.105',
            '--ports' => '22,80,443',
        ])
            ->expectsOutput('Firewall rules created for ports: 22, 80, 443')
            ->assertExitCode(Command::SUCCESS);

        $server = Server::query()->where('ip', '192.168.1.105')->first();

        $this->assertCount(3, $server->firewallRules);

        $this->assertDatabaseHas('firewall_rules', [
            'server_id' => $server->id,
            'port' => 22,
            'type' => 'allow',
            'protocol' => 'tcp',
            'status' => FirewallRuleStatus::READY,
        ]);

        $this->assertDatabaseHas('firewall_rules', [
            'server_id' => $server->id,
            'port' => 80,
            'type' => 'allow',
            'protocol' => 'tcp',
            'status' => FirewallRuleStatus::READY,
        ]);

        $this->assertDatabaseHas('firewall_rules', [
            'server_id' => $server->id,
            'port' => 443,
            'type' => 'allow',
            'protocol' => 'tcp',
            'status' => FirewallRuleStatus::READY,
        ]);
    }

    public function test_create_local_server_with_nginx_and_firewall_rules(): void
    {
        $this->artisan('servers:create-local', [
            'ip' => '192.168.1.106',
            '--nginx' => 'Y',
            '--ports' => '22,80,443,3306',
        ])
            ->expectsOutput('Nginx service created.')
            ->expectsOutput('Firewall rules created for ports: 22, 80, 443, 3306')
            ->assertExitCode(Command::SUCCESS);

        $server = Server::query()->where('ip', '192.168.1.106')->first();

        $this->assertCount(1, $server->services()->where('name', 'nginx')->get());
        $this->assertCount(4, $server->firewallRules);
    }

    public function test_fails_when_server_with_ip_already_exists(): void
    {
        Server::factory()->create([
            'ip' => '192.168.1.200',
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $this->artisan('servers:create-local', [
            'ip' => '192.168.1.200',
        ])
            ->expectsOutput('A server with IP 192.168.1.200 already exists.')
            ->assertExitCode(Command::FAILURE);
    }

    public function test_fails_with_invalid_ip_address(): void
    {
        $this->artisan('servers:create-local', [
            'ip' => 'invalid-ip',
        ])
            ->expectsOutput('Invalid IP address: invalid-ip')
            ->assertExitCode(Command::FAILURE);

        $this->assertDatabaseMissing('servers', ['ip' => 'invalid-ip']);
    }

    public function test_fails_with_malformed_ip_address(): void
    {
        $this->artisan('servers:create-local', [
            'ip' => '999.999.999.999',
        ])
            ->expectsOutput('Invalid IP address: 999.999.999.999')
            ->assertExitCode(Command::FAILURE);

        $this->assertDatabaseMissing('servers', ['ip' => '999.999.999.999']);
    }

    public function test_accepts_valid_ipv6_address(): void
    {
        $this->artisan('servers:create-local', [
            'ip' => '::1',
        ])
            ->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseHas('servers', ['ip' => '::1']);
    }

    public function test_fails_when_no_user_exists(): void
    {
        User::query()->delete();

        $this->artisan('servers:create-local', [
            'ip' => '192.168.1.107',
        ])
            ->expectsOutput('No user found. Please create a user first or specify a valid user ID.')
            ->assertExitCode(Command::FAILURE);
    }

    public function test_fails_when_no_project_exists(): void
    {
        Project::query()->delete();

        $this->artisan('servers:create-local', [
            'ip' => '192.168.1.108',
        ])
            ->expectsOutput('No project found. Please create a project first or specify a valid project ID.')
            ->assertExitCode(Command::FAILURE);
    }

    public function test_fails_with_invalid_user_id(): void
    {
        $this->artisan('servers:create-local', [
            'ip' => '192.168.1.109',
            '--user' => 99999,
        ])
            ->expectsOutput('No user found. Please create a user first or specify a valid user ID.')
            ->assertExitCode(Command::FAILURE);
    }

    public function test_fails_with_invalid_project_id(): void
    {
        $this->artisan('servers:create-local', [
            'ip' => '192.168.1.110',
            '--project' => 99999,
        ])
            ->expectsOutput('No project found. Please create a project first or specify a valid project ID.')
            ->assertExitCode(Command::FAILURE);
    }

    public function test_uses_specified_user_id(): void
    {
        $newUser = User::factory()->create();
        $newUser->ensureHasDefaultProject();

        $this->artisan('servers:create-local', [
            'ip' => '192.168.1.111',
            '--user' => $newUser->id,
        ])
            ->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseHas('servers', [
            'ip' => '192.168.1.111',
            'user_id' => $newUser->id,
        ]);
    }

    public function test_uses_specified_project_id(): void
    {
        $newProject = Project::factory()->create();
        UserProject::create([
            'user_id' => $this->user->id,
            'project_id' => $newProject->id,
            'role' => UserRole::USER,
        ]);

        $this->artisan('servers:create-local', [
            'ip' => '192.168.1.112',
            '--project' => $newProject->id,
        ])
            ->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseHas('servers', [
            'ip' => '192.168.1.112',
            'project_id' => $newProject->id,
        ]);
    }

    public function test_skips_invalid_ports(): void
    {
        $this->artisan('servers:create-local', [
            'ip' => '192.168.1.113',
            '--ports' => '22,invalid,80,abc,443',
        ])
            ->expectsOutput('Skipping invalid port: invalid')
            ->expectsOutput('Skipping invalid port: abc')
            ->expectsOutput('Firewall rules created for ports: 22, invalid, 80, abc, 443')
            ->assertExitCode(Command::SUCCESS);

        $server = Server::query()->where('ip', '192.168.1.113')->first();
        $this->assertCount(3, $server->firewallRules);

        $ports = $server->firewallRules->pluck('port')->toArray();
        $this->assertContains(22, $ports);
        $this->assertContains(80, $ports);
        $this->assertContains(443, $ports);
    }

    public function test_handles_ports_with_spaces(): void
    {
        $this->artisan('servers:create-local', [
            'ip' => '192.168.1.114',
            '--ports' => '22, 80, 443',
        ])
            ->assertExitCode(Command::SUCCESS);

        $server = Server::query()->where('ip', '192.168.1.114')->first();
        $this->assertCount(3, $server->firewallRules);
    }

    public function test_server_has_correct_default_values(): void
    {
        $this->artisan('servers:create-local', [
            'ip' => '192.168.1.115',
        ])
            ->assertExitCode(Command::SUCCESS);

        $server = Server::query()->where('ip', '192.168.1.115')->first();

        $this->assertEquals(22, $server->port);
        $this->assertEquals(OperatingSystem::UBUNTU22, $server->os);
        $this->assertEquals(Custom::id(), $server->provider);
        $this->assertEquals('192.168.1.115', $server->local_ip);
        $this->assertTrue($server->is_local);
        $this->assertEquals(ServerStatus::READY, $server->status);
        $this->assertEquals(100, $server->progress);

        // Verify local_data defaults
        $this->assertIsArray($server->local_data);
        $this->assertNull($server->local_data['domain']);
        $this->assertEquals(3000, $server->local_data['port']);
        $this->assertFalse($server->local_data['ssl_enabled']);
    }

    public function test_firewall_rules_have_correct_default_values(): void
    {
        $this->artisan('servers:create-local', [
            'ip' => '192.168.1.116',
            '--ports' => '8080',
        ])
            ->assertExitCode(Command::SUCCESS);

        $server = Server::query()->where('ip', '192.168.1.116')->first();
        $rule = $server->firewallRules->first();

        $this->assertEquals('port-8080', $rule->name);
        $this->assertEquals('allow', $rule->type);
        $this->assertEquals('tcp', $rule->protocol);
        $this->assertEquals(8080, $rule->port);
        $this->assertEquals('0.0.0.0', $rule->source);
        $this->assertEquals('0', $rule->mask);
        $this->assertEquals('Created by servers:create-local command', $rule->note);
        $this->assertEquals(FirewallRuleStatus::READY, $rule->status);
    }

    public function test_nginx_service_has_correct_default_values(): void
    {
        $this->artisan('servers:create-local', [
            'ip' => '192.168.1.117',
            '--nginx' => 'Y',
        ])
            ->assertExitCode(Command::SUCCESS);

        $server = Server::query()->where('ip', '192.168.1.117')->first();
        $service = $server->services()->where('name', 'nginx')->first();

        $this->assertEquals('webserver', $service->type);
        $this->assertEquals('nginx', $service->name);
        $this->assertEquals('latest', $service->version);
        $this->assertEquals(ServiceStatus::READY, $service->status);
        $this->assertTrue($service->is_default);
    }

    public function test_default_services_created_when_nginx_not_specified(): void
    {
        $this->artisan('servers:create-local', [
            'ip' => '192.168.1.118',
        ])
            ->assertExitCode(Command::SUCCESS);

        $server = Server::query()->where('ip', '192.168.1.118')->first();
        // VitoLocal and Firewall (UFW) services are always created
        $this->assertCount(2, $server->services);
        $this->assertTrue($server->services->contains('name', 'vito-local'));
        $this->assertTrue($server->services->contains('name', 'ufw'));
    }

    public function test_no_firewall_rules_created_when_ports_not_specified(): void
    {
        $this->artisan('servers:create-local', [
            'ip' => '192.168.1.119',
        ])
            ->assertExitCode(Command::SUCCESS);

        $server = Server::query()->where('ip', '192.168.1.119')->first();
        $this->assertCount(0, $server->firewallRules);
    }

    public function test_server_assigned_to_user_current_project_by_default(): void
    {
        $this->artisan('servers:create-local', [
            'ip' => '192.168.1.120',
        ])
            ->assertExitCode(Command::SUCCESS);

        $server = Server::query()->where('ip', '192.168.1.120')->first();
        $this->assertEquals($this->user->current_project_id, $server->project_id);
        $this->assertEquals($this->user->id, $server->user_id);
    }

    public function test_create_multiple_local_servers(): void
    {
        $this->artisan('servers:create-local', [
            'ip' => '192.168.1.121',
            '--name' => 'server-1',
        ])
            ->assertExitCode(Command::SUCCESS);

        $this->artisan('servers:create-local', [
            'ip' => '192.168.1.122',
            '--name' => 'server-2',
        ])
            ->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseHas('servers', ['ip' => '192.168.1.121', 'name' => 'server-1']);
        $this->assertDatabaseHas('servers', ['ip' => '192.168.1.122', 'name' => 'server-2']);
    }

    public function test_empty_ports_option_creates_no_firewall_rules(): void
    {
        $this->artisan('servers:create-local', [
            'ip' => '192.168.1.123',
            '--ports' => '',
        ])
            ->assertExitCode(Command::SUCCESS);

        $server = Server::query()->where('ip', '192.168.1.123')->first();
        $this->assertCount(0, $server->firewallRules);
    }

    public function test_local_data_has_default_values(): void
    {
        $this->artisan('servers:create-local', [
            'ip' => '192.168.1.124',
        ])
            ->assertExitCode(Command::SUCCESS);

        $server = Server::query()->where('ip', '192.168.1.124')->first();

        $this->assertIsArray($server->local_data);
        $this->assertNull($server->local_data['domain']);
        $this->assertEquals(3000, $server->local_data['port']);
        $this->assertFalse($server->local_data['ssl_enabled']);
    }

    public function test_local_data_with_domain(): void
    {
        $this->artisan('servers:create-local', [
            'ip' => '192.168.1.125',
            '--domain' => 'example.local',
        ])
            ->assertExitCode(Command::SUCCESS);

        $server = Server::query()->where('ip', '192.168.1.125')->first();

        $this->assertEquals('example.local', $server->local_data['domain']);
    }

    public function test_local_data_with_custom_web_port(): void
    {
        $this->artisan('servers:create-local', [
            'ip' => '192.168.1.126',
            '--web-port' => '8080',
        ])
            ->assertExitCode(Command::SUCCESS);

        $server = Server::query()->where('ip', '192.168.1.126')->first();

        $this->assertEquals(8080, $server->local_data['port']);
    }

    public function test_local_data_with_ssl_enabled(): void
    {
        $this->artisan('servers:create-local', [
            'ip' => '192.168.1.127',
            '--ssl' => 'Y',
        ])
            ->assertExitCode(Command::SUCCESS);

        $server = Server::query()->where('ip', '192.168.1.127')->first();

        $this->assertTrue($server->local_data['ssl_enabled']);
    }

    public function test_local_data_with_ssl_enabled_lowercase(): void
    {
        $this->artisan('servers:create-local', [
            'ip' => '192.168.1.128',
            '--ssl' => 'y',
        ])
            ->assertExitCode(Command::SUCCESS);

        $server = Server::query()->where('ip', '192.168.1.128')->first();

        $this->assertTrue($server->local_data['ssl_enabled']);
    }

    public function test_local_data_with_ssl_disabled(): void
    {
        $this->artisan('servers:create-local', [
            'ip' => '192.168.1.129',
            '--ssl' => 'N',
        ])
            ->assertExitCode(Command::SUCCESS);

        $server = Server::query()->where('ip', '192.168.1.129')->first();

        $this->assertFalse($server->local_data['ssl_enabled']);
    }

    public function test_local_data_with_all_options(): void
    {
        $this->artisan('servers:create-local', [
            'ip' => '192.168.1.130',
            '--domain' => 'myapp.local',
            '--web-port' => '443',
            '--ssl' => 'Y',
        ])
            ->assertExitCode(Command::SUCCESS);

        $server = Server::query()->where('ip', '192.168.1.130')->first();

        $this->assertIsArray($server->local_data);
        $this->assertEquals('myapp.local', $server->local_data['domain']);
        $this->assertEquals(443, $server->local_data['port']);
        $this->assertTrue($server->local_data['ssl_enabled']);
    }

    public function test_local_data_with_all_command_options(): void
    {
        $this->artisan('servers:create-local', [
            'ip' => '192.168.1.131',
            '--name' => 'full-server',
            '--nginx' => 'Y',
            '--ports' => '22,80,443',
            '--domain' => 'fulltest.local',
            '--web-port' => '8443',
            '--ssl' => 'Y',
        ])
            ->assertExitCode(Command::SUCCESS);

        $server = Server::query()->where('ip', '192.168.1.131')->first();

        // Verify server basics
        $this->assertEquals('full-server', $server->name);
        $this->assertTrue($server->is_local);

        // Verify local_data
        $this->assertEquals('fulltest.local', $server->local_data['domain']);
        $this->assertEquals(8443, $server->local_data['port']);
        $this->assertTrue($server->local_data['ssl_enabled']);

        // Verify nginx service
        $this->assertCount(1, $server->services()->where('name', 'nginx')->get());

        // Verify firewall rules
        $this->assertCount(3, $server->firewallRules);
    }

    public function test_local_data_persists_after_refresh(): void
    {
        $this->artisan('servers:create-local', [
            'ip' => '192.168.1.132',
            '--domain' => 'persist.local',
            '--web-port' => '3000',
            '--ssl' => 'Y',
        ])
            ->assertExitCode(Command::SUCCESS);

        // Fetch fresh from database
        $server = Server::query()->where('ip', '192.168.1.132')->first();
        $server->refresh();

        $this->assertEquals('persist.local', $server->local_data['domain']);
        $this->assertEquals(3000, $server->local_data['port']);
        $this->assertTrue($server->local_data['ssl_enabled']);
    }
}
