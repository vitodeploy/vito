<?php

use App\Models\Site;
use App\Services\ProcessManager\Supervisor;
use App\SiteTypes\NodeSite;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
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
});

test('start command returns type data value', function () {
    $reflection = new ReflectionMethod($this->siteType, 'startCommand');

    expect($reflection->invoke($this->siteType))->toEqual('npm run start');
});

test('supervisor worker template renders environment', function () {
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
});

test('supervisor worker template without environment', function () {
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
});

test('format environment escapes unsafe values', function () {
    $formatted = Supervisor::formatEnvironment([
        'PERCENT' => 'a%b',
        'QUOTED' => 'a"b',
        'MULTILINE' => "a\r\nb",
        '1INVALID' => 'dropped',
        'NODE_ENV' => 'production',
    ]);

    expect($formatted)->toBe('PERCENT="a%%b",QUOTED="ab",MULTILINE="ab",NODE_ENV="production"');
});

test('format environment returns empty string for empty map', function () {
    expect(Supervisor::formatEnvironment([]))->toBe('');
});
