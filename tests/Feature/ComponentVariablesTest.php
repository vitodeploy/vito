<?php

use App\Actions\Component\WriteComponentEnv;
use App\Facades\SSH;
use App\Helpers\ComponentVariables;
use App\Models\Application;
use App\Models\Component;
use App\Models\ComponentCapability;
use App\Models\Environment;
use App\Models\EnvironmentVariable;
use App\Models\ServerLog;
use App\RuntimePresets\Laravel;
use App\RuntimePresets\NodeJS;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    SSH::fake('');
    Http::preventStrayRequests();
    $this->application = Application::factory()->create(['project_id' => $this->server->project_id, 'runtime' => 'node']);
    $this->environment = Environment::factory()->for($this->application)->create();
    $this->component = Component::factory()->for($this->environment)->for($this->server)->create(['user' => 'runtime']);
});

test('component variables merge the resource slot environment and proxy port in order', function () {
    EnvironmentVariable::factory()->for($this->environment)->create(['key' => 'PORT', 'value' => '3000', 'is_secret' => true]);
    EnvironmentVariable::factory()->for($this->environment)->create(['key' => 'SHARED', 'value' => 'environment', 'is_secret' => false]);
    ComponentCapability::factory()->for($this->component)->create(['capability' => 'web', 'config' => ['port' => 12345]]);
    $resolver = new class extends ComponentVariables
    {
        protected static function resources(Component $component): array
        {
            return [
                'SHARED' => ['value' => 'resource', 'source' => 'resource', 'is_secret' => true],
                'RESOURCE_ONLY' => ['value' => 'resource', 'source' => 'resource', 'is_secret' => true],
            ];
        }
    };

    expect($resolver::resolve($this->component))->toBe([
        'SHARED' => ['value' => 'environment', 'source' => 'environment', 'is_secret' => false],
        'RESOURCE_ONLY' => ['value' => 'resource', 'source' => 'resource', 'is_secret' => true],
        'PORT' => ['value' => '12345', 'source' => 'vito', 'is_secret' => false],
    ]);
    $other = Component::factory()->for($this->environment)->create();
    ComponentCapability::factory()->for($other)->create(['capability' => 'web', 'config' => ['port' => 23456]]);
    expect(ComponentVariables::resolve($other)['PORT']['value'])->toBe('23456')
        ->and(ComponentVariables::resolve($other)['SHARED'])->toBe(ComponentVariables::resolve($this->component)['SHARED']);
});

test('computed port is absent for non proxy presets and components without web', function (string $runtime, bool $web) {
    $this->application->update(['runtime' => $runtime]);
    if ($web) {
        ComponentCapability::factory()->for($this->component)->create(['capability' => 'web', 'config' => ['port' => 12345]]);
    }
    expect(ComponentVariables::resolve($this->component))->toBe([]);
    EnvironmentVariable::factory()->for($this->environment)->create(['key' => 'PORT', 'value' => 'user-value', 'is_secret' => true]);
    expect(ComponentVariables::resolve($this->component->fresh())['PORT'])->toBe(['value' => 'user-value', 'source' => 'environment', 'is_secret' => true]);
})->with([['php', true], ['static', true], ['node', false], ['bun', false]]);

test('component env is uploaded privately with an atomic executable loader and never enters commands or logs', function () {
    EnvironmentVariable::factory()->for($this->environment)->create(['key' => 'PASSWORD', 'value' => 'never-log-this-secret', 'is_secret' => true]);
    $uploads = [];
    $ssh = Mockery::mock(\App\Support\Testing\SSHFake::class, [''])->makePartial();
    $ssh->shouldReceive('upload')->twice()->withArgs(function (string $local, string $remote, ?string $owner, ?string $log, ?int $siteId, string $permission) use (&$uploads): bool {
        $uploads[] = file_get_contents($local);

        return $owner === 'runtime' && $permission === '600';
    })->andReturnNull();
    SSH::swap($ssh);
    app(WriteComponentEnv::class)->write($this->component, $this->component->envPath());

    expect($uploads)->toHaveCount(2)
        ->and($uploads[0])->toBe(view('ssh.applications.env-exec')->render())
        ->and($uploads[1])->toBe("PASSWORD='never-log-this-secret'");
    SSH::assertExecutedContains('umask 077');
    SSH::assertExecutedContains('set -C');
    SSH::assertExecutedContains('chmod 600 -- ');
    SSH::assertExecutedContains('chmod 700 -- ');
    SSH::assertExecutedContains('mv -f -- ');
    SSH::assertExecutedContains(escapeshellarg($this->component->basePath().'/vito-env-exec'));
    SSH::assertExecutedContains(escapeshellarg($this->component->envPath()));
    SSH::assertNotExecutedContains('never-log-this-secret');
    foreach (ServerLog::query()->get() as $log) {
        expect($log->getContent())->not->toContain('never-log-this-secret');
    }
});

test('component env staging files are cleaned after failure without hiding the original error', function (string $stage, bool $cleanupFails) {
    EnvironmentVariable::factory()->for($this->environment)->create(['key' => 'PASSWORD', 'value' => 'never-log-this-secret', 'is_secret' => true]);
    $failure = new \App\Exceptions\SSHCommandError(message: 'Original write failed');
    $data = [];
    $uploads = 0;
    $ssh = Mockery::mock(\App\Support\Testing\SSHFake::class, [''])->makePartial();
    $ssh->shouldReceive('exec')->once()->withArgs(function ($view) use (&$data): bool {
        if ($view->getData()['operation'] !== 'prepare') {
            return false;
        }
        $data = $view->getData();

        return true;
    })->andReturn('');
    $ssh->shouldReceive('write')->twice()->andReturnUsing(function () use (&$uploads, $stage, $failure): void {
        $uploads++;
        if ($uploads === 2 && $stage === 'upload') {
            throw $failure;
        }
    });
    if ($stage === 'install') {
        $ssh->shouldReceive('exec')->once()->withArgs(fn ($view): bool => $view->getData()['operation'] === 'install')->andThrow($failure);
    }
    $ssh->shouldReceive('exec')->once()->withArgs(function ($view) use (&$data): bool {
        if ($view->getData()['operation'] !== 'cleanup') {
            return false;
        }
        expect($view->render())->toContain('rm -f -- '.escapeshellarg($data['envTemp']).' '.escapeshellarg($data['loaderTemp']))
            ->not->toContain('never-log-this-secret', escapeshellarg($data['path']), escapeshellarg($data['loader']));

        return true;
    })->andReturnUsing(function () use ($cleanupFails): string {
        if ($cleanupFails) {
            throw new \App\Exceptions\SSHCommandError(message: 'Cleanup failed');
        }

        return '';
    });
    SSH::swap($ssh);

    try {
        app(WriteComponentEnv::class)->write($this->component, $this->component->envPath());
        $this->fail('The write must fail.');
    } catch (\App\Exceptions\SSHCommandError $e) {
        expect($e)->toBe($failure);
    }
})->with(['upload', 'install'])->with([false, true]);

test('component env loader decodes literal records without evaluating the file', function () {
    $loader = view('ssh.applications.env-exec')->render();
    expect($loader)->toContain("#!/bin/bash -p", "/usr/bin/awk", "read -r -d ''", 'export "$kv"', 'exec "$@"', 'Invalid env file at line %d.', "|| exit 1", 'if (key ~ /'.EnvironmentVariable::RESERVED_KEY_PATTERN.'/) invalid()')
        ->not->toMatch('/\b(?:source|eval)\s|(?:^|\n)\s*\.\s/');
});

test('Laravel warnings apply only with multiple web components and other presets have none', function () {
    $preset = new Laravel;
    expect($preset->warnings($this->environment))->toBe([]);
    ComponentCapability::factory()->for($this->component)->create(['capability' => 'web']);
    expect($preset->warnings($this->environment))->toBe([]);
    $worker = Component::factory()->for($this->environment)->create();
    ComponentCapability::factory()->for($worker)->create(['capability' => 'worker']);
    expect($preset->warnings($this->environment))->toBe([]);
    ComponentCapability::factory()->for($worker)->create(['capability' => 'web']);
    expect($preset->warnings($this->environment))->toHaveCount(2)
        ->and((new NodeJS)->warnings($this->environment))->toBe([]);
});
