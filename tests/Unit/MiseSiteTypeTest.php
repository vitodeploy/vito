<?php

namespace Tests\Unit;

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

        $this->miseSite = Site::factory()->create([
            'server_id' => $this->server->id,
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

    public function test_worker_command_returns_start_command(): void
    {
        $reflection = new \ReflectionMethod($this->siteType, 'workerCommand');

        $command = $reflection->invoke($this->siteType);

        $this->assertEquals('npm run start', $command);
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
