<?php

namespace Tests\Unit;

use App\Models\Server;
use App\Models\Site;
use App\SiteTypes\MiseNodeJS;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MiseSiteTypeTest extends TestCase
{
    use RefreshDatabase;

    protected Site $miseSite;

    protected MiseNodeJS $siteType;

    protected function setUp(): void
    {
        parent::setUp();

        $server = Server::factory()->create();
        $this->miseSite = Site::factory()->create([
            'server_id' => $server->id,
            'user' => 'testuser',
            'path' => '/home/testuser/example.com',
            'type' => MiseNodeJS::id(),
            'type_data' => [
                'node_version' => '22',
                'package_manager' => 'npm',
                'build_command' => 'npm run build',
                'start_command' => 'npm run start',
            ],
        ]);
        $this->siteType = new MiseNodeJS($this->miseSite);
    }

    public function test_worker_environment_contains_path(): void
    {
        $reflection = new \ReflectionMethod($this->siteType, 'workerEnvironment');

        $environment = $reflection->invoke($this->siteType);

        $this->assertIsArray($environment);
        $this->assertArrayHasKey('PATH', $environment);
        $this->assertStringContainsString('/home/testuser/.local/share/mise/shims', $environment['PATH']);
    }

    public function test_shim_path_contains_mise_shims_and_standard_paths(): void
    {
        $reflection = new \ReflectionMethod($this->siteType, 'shimPath');

        $shimPath = $reflection->invoke($this->siteType);

        $this->assertStringContainsString('/home/testuser/.local/share/mise/shims', $shimPath);
        $this->assertStringContainsString('/usr/local/bin', $shimPath);
        $this->assertStringContainsString('/usr/bin', $shimPath);
        $this->assertStringContainsString('/bin', $shimPath);
        $this->assertStringContainsString('/home/testuser/.local/bin', $shimPath);
    }

    public function test_worker_command_returns_start_command(): void
    {
        $reflection = new \ReflectionMethod($this->siteType, 'workerCommand');

        $command = $reflection->invoke($this->siteType);

        $this->assertEquals('npm run start', $command);
    }

    public function test_wrap_command_without_cd(): void
    {
        $reflection = new \ReflectionMethod($this->siteType, 'wrapCommand');

        $command = $reflection->invoke($this->siteType, 'npm install', false);

        $this->assertStringStartsWith('bash -c "export PATH=', $command);
        $this->assertStringContainsString('npm install', $command);
        $this->assertStringNotContainsString('cd /home/testuser/example.com', $command);
    }

    public function test_wrap_command_with_cd(): void
    {
        $reflection = new \ReflectionMethod($this->siteType, 'wrapCommand');

        $command = $reflection->invoke($this->siteType, 'npm install', true);

        $this->assertStringStartsWith('bash -c "export PATH=', $command);
        $this->assertStringContainsString('cd /home/testuser/example.com &&', $command);
        $this->assertStringContainsString('npm install', $command);
    }

    public function test_mise_shims_path_uses_site_user(): void
    {
        $reflection = new \ReflectionMethod($this->siteType, 'miseShimsPath');

        $path = $reflection->invoke($this->siteType);

        $this->assertEquals('/home/testuser/.local/share/mise/shims', $path);
    }

    public function test_mise_shims_path_uses_different_user(): void
    {
        $server = Server::factory()->create();
        $site = Site::factory()->create([
            'server_id' => $server->id,
            'user' => 'anotheruser',
            'path' => '/home/anotheruser/example.com',
            'type' => MiseNodeJS::id(),
        ]);

        $siteType = new MiseNodeJS($site);
        $reflection = new \ReflectionMethod($siteType, 'miseShimsPath');

        $path = $reflection->invoke($siteType);

        $this->assertEquals('/home/anotheruser/.local/share/mise/shims', $path);
    }

    public function test_supervisor_worker_template_renders_environment(): void
    {
        $rendered = view('ssh.services.process-manager.supervisor.worker', [
            'name' => '1',
            'directory' => '/home/testuser/example.com',
            'command' => 'npm run start',
            'user' => 'testuser',
            'autoStart' => 'true',
            'autoRestart' => 'true',
            'numprocs' => '1',
            'logFile' => '/home/testuser/.logs/workers/1.log',
            'environment' => [
                'PATH' => '/home/testuser/.local/share/mise/shims:/usr/local/bin:/usr/bin:/bin',
                'NODE_ENV' => 'production',
            ],
        ])->render();

        $this->assertStringContainsString('[program:1]', $rendered);
        $this->assertStringContainsString('command=npm run start', $rendered);
        $this->assertStringContainsString('environment=PATH="/home/testuser/.local/share/mise/shims:/usr/local/bin:/usr/bin:/bin",NODE_ENV="production"', $rendered);
    }

    public function test_supervisor_worker_template_without_environment(): void
    {
        $rendered = view('ssh.services.process-manager.supervisor.worker', [
            'name' => '1',
            'directory' => '/home/testuser/example.com',
            'command' => 'php artisan queue:work',
            'user' => 'testuser',
            'autoStart' => 'true',
            'autoRestart' => 'true',
            'numprocs' => '1',
            'logFile' => '/home/testuser/.logs/workers/1.log',
            'environment' => null,
        ])->render();

        $this->assertStringContainsString('[program:1]', $rendered);
        $this->assertStringContainsString('command=php artisan queue:work', $rendered);
        $this->assertStringNotContainsString('environment=', $rendered);
    }
}
