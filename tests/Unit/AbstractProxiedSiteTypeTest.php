<?php

namespace Tests\Unit;

use App\Models\Site;
use App\Services\ProcessManager\Supervisor;
use App\SiteTypes\NodeSite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbstractProxiedSiteTypeTest extends TestCase
{
    use RefreshDatabase;

    protected Site $proxiedSite;

    protected NodeSite $siteType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->proxiedSite = Site::factory()->create([
            'server_id' => $this->server->id,
            'user' => 'testuser',
            'path' => '/home/testuser/example.com',
            'type' => NodeSite::id(),
            'type_data' => [
                'node_version' => '22',
                'package_manager' => 'npm',
                'build_command' => 'npm run build',
                'start_command' => 'npm run start',
            ],
        ]);
        $this->siteType = new NodeSite($this->proxiedSite);
    }

    public function test_start_command_returns_type_data_value(): void
    {
        $reflection = new \ReflectionMethod($this->siteType, 'startCommand');

        $this->assertEquals('npm run start', $reflection->invoke($this->siteType));
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
            'environment' => Supervisor::formatEnvironment([
                'PATH' => '/home/testuser/.local/share/mise/shims:/usr/local/bin:/usr/bin:/bin',
                'NODE_ENV' => 'production',
            ]),
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
            'environment' => Supervisor::formatEnvironment([]),
        ])->render();

        $this->assertStringContainsString('[program:1]', $rendered);
        $this->assertStringContainsString('command=php artisan queue:work', $rendered);
        $this->assertStringNotContainsString('environment=', $rendered);
    }

    public function test_format_environment_escapes_unsafe_values(): void
    {
        $formatted = Supervisor::formatEnvironment([
            'PERCENT' => 'a%b',
            'QUOTED' => 'a"b',
            'MULTILINE' => "a\r\nb",
            '1INVALID' => 'dropped',
            'NODE_ENV' => 'production',
        ]);

        $this->assertSame('PERCENT="a%%b",QUOTED="ab",MULTILINE="ab",NODE_ENV="production"', $formatted);
    }

    public function test_format_environment_returns_empty_string_for_empty_map(): void
    {
        $this->assertSame('', Supervisor::formatEnvironment([]));
    }
}
