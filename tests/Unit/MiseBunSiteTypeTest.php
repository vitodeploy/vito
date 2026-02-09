<?php

namespace Tests\Unit;

use App\Models\Server;
use App\Models\Site;
use App\SiteTypes\MiseBun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MiseBunSiteTypeTest extends TestCase
{
    use RefreshDatabase;

    protected Site $miseSite;

    protected MiseBun $siteType;

    protected function setUp(): void
    {
        parent::setUp();

        $server = Server::factory()->create();
        $this->miseSite = Site::factory()->create([
            'server_id' => $server->id,
            'user' => 'testuser',
            'path' => '/home/testuser/example.com',
            'type' => MiseBun::id(),
            'type_data' => [
                'bun_version' => '1.2',
                'build_command' => 'bun run build',
                'start_command' => 'bun run start',
            ],
        ]);
        $this->siteType = new MiseBun($this->miseSite);
    }

    public function test_id(): void
    {
        $this->assertEquals('mise_bun', MiseBun::id());
    }

    public function test_language(): void
    {
        $this->assertEquals('bun', $this->siteType->language());
    }

    public function test_required_services(): void
    {
        $this->assertEquals(['webserver', 'process_manager'], $this->siteType->requiredServices());
    }

    public function test_runtime_version_from_type_data(): void
    {
        $reflection = new \ReflectionMethod($this->siteType, 'runtimeVersion');

        $this->assertEquals('1.2', $reflection->invoke($this->siteType));
    }

    public function test_runtime_version_defaults_to_1_2(): void
    {
        $server = Server::factory()->create();
        $site = Site::factory()->create([
            'server_id' => $server->id,
            'user' => 'testuser',
            'path' => '/home/testuser/example.com',
            'type' => MiseBun::id(),
            'type_data' => [],
        ]);
        $siteType = new MiseBun($site);
        $reflection = new \ReflectionMethod($siteType, 'runtimeVersion');

        $this->assertEquals('1.2', $reflection->invoke($siteType));
    }

    public function test_runtime(): void
    {
        $reflection = new \ReflectionMethod($this->siteType, 'runtime');

        $this->assertEquals('bun', $reflection->invoke($this->siteType));
    }

    public function test_worker_command_returns_start_command(): void
    {
        $reflection = new \ReflectionMethod($this->siteType, 'workerCommand');

        $command = $reflection->invoke($this->siteType);

        $this->assertEquals('bun run start', $command);
    }

    public function test_build_command_from_type_data(): void
    {
        $reflection = new \ReflectionMethod($this->siteType, 'buildCommand');

        $this->assertEquals('bun run build', $reflection->invoke($this->siteType));
    }

    public function test_build_command_defaults(): void
    {
        $server = Server::factory()->create();
        $site = Site::factory()->create([
            'server_id' => $server->id,
            'user' => 'testuser',
            'path' => '/home/testuser/example.com',
            'type' => MiseBun::id(),
            'type_data' => [],
        ]);
        $siteType = new MiseBun($site);
        $reflection = new \ReflectionMethod($siteType, 'buildCommand');

        $this->assertEquals('bun run build', $reflection->invoke($siteType));
    }

    public function test_start_command_from_type_data(): void
    {
        $reflection = new \ReflectionMethod($this->siteType, 'startCommand');

        $this->assertEquals('bun run start', $reflection->invoke($this->siteType));
    }

    public function test_start_command_defaults(): void
    {
        $server = Server::factory()->create();
        $site = Site::factory()->create([
            'server_id' => $server->id,
            'user' => 'testuser',
            'path' => '/home/testuser/example.com',
            'type' => MiseBun::id(),
            'type_data' => [],
        ]);
        $siteType = new MiseBun($site);
        $reflection = new \ReflectionMethod($siteType, 'startCommand');

        $this->assertEquals('bun run start', $reflection->invoke($siteType));
    }

    public function test_data_with_defaults(): void
    {
        $data = $this->siteType->data([]);

        $this->assertEquals([
            'bun_version' => '1.2',
            'build_command' => 'bun run build',
            'start_command' => 'bun run start',
        ], $data);
    }

    public function test_data_with_custom_commands(): void
    {
        $data = $this->siteType->data([
            'bun_version' => '1.1',
            'build_command' => 'bun run build:prod',
            'start_command' => 'bun run start:prod',
        ]);

        $this->assertEquals([
            'bun_version' => '1.1',
            'build_command' => 'bun run build:prod',
            'start_command' => 'bun run start:prod',
        ], $data);
    }

    public function test_create_fields(): void
    {
        $fields = $this->siteType->createFields([
            'source_control' => '1',
            'repository' => 'org/repo',
            'branch' => 'main',
            'port' => '3000',
        ]);

        $this->assertEquals([
            'source_control_id' => '1',
            'repository' => 'org/repo',
            'branch' => 'main',
            'port' => '3000',
        ], $fields);
    }

    public function test_create_rules_contain_bun_version(): void
    {
        $rules = $this->siteType->createRules([]);

        $this->assertArrayHasKey('bun_version', $rules);
        $this->assertArrayHasKey('source_control', $rules);
        $this->assertArrayHasKey('repository', $rules);
        $this->assertArrayHasKey('branch', $rules);
        $this->assertArrayHasKey('port', $rules);
        $this->assertArrayHasKey('build_command', $rules);
        $this->assertArrayHasKey('start_command', $rules);
        $this->assertArrayNotHasKey('package_manager', $rules);
    }

    public function test_wrap_command_with_cd(): void
    {
        $reflection = new \ReflectionMethod($this->siteType, 'wrapCommand');

        $command = $reflection->invoke($this->siteType, 'bun install', true);

        $this->assertStringStartsWith('bash -c "export PATH=', $command);
        $this->assertStringContainsString('cd /home/testuser/example.com &&', $command);
        $this->assertStringContainsString('bun install', $command);
    }

    public function test_wrap_command_without_cd(): void
    {
        $reflection = new \ReflectionMethod($this->siteType, 'wrapCommand');

        $command = $reflection->invoke($this->siteType, 'bun install', false);

        $this->assertStringStartsWith('bash -c "export PATH=', $command);
        $this->assertStringContainsString('bun install', $command);
        $this->assertStringNotContainsString('cd /home/testuser/example.com', $command);
    }

    public function test_worker_environment_contains_path(): void
    {
        $reflection = new \ReflectionMethod($this->siteType, 'workerEnvironment');

        $environment = $reflection->invoke($this->siteType);

        $this->assertIsArray($environment);
        $this->assertArrayHasKey('PATH', $environment);
        $this->assertStringContainsString('/home/testuser/.local/share/mise/shims', $environment['PATH']);
    }

    public function test_base_commands_returns_empty_array(): void
    {
        $this->assertEquals([], $this->siteType->baseCommands());
    }
}
