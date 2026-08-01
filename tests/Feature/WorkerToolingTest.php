<?php

use App\Actions\Worker\RefreshSiteWorkerConfigs;
use App\Enums\SiteStatus;
use App\Enums\WorkerStatus;
use App\Facades\SSH;
use App\Models\Site;
use App\Models\SourceControl;
use App\Models\Worker;
use App\SiteTypes\Laravel;
use App\SiteTypes\LoadBalancer;
use App\SourceControlProviders\Github;
use App\Tooling\ToolingRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $sourceControl = SourceControl::factory()->create([
        'provider' => Github::id(),
        'user_id' => $this->user->id,
    ]);

    $this->isolatedSite = Site::factory()->create([
        'server_id' => $this->server->id,
        'domain' => 'iso.test',
        'user' => 'isolated-foo',
        'path' => '/home/isolated-foo/iso.test',
        'source_control_id' => $sourceControl->id,
        'type' => Laravel::id(),
        'status' => SiteStatus::READY,
        'type_data' => [],
    ]);
});

test('effective environment is empty when no tooling installed', function () {
    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->isolatedSite->id,
        'user' => 'isolated-foo',
        'command' => 'php artisan queue:work',
        'environment' => null,
        'status' => WorkerStatus::RUNNING,
    ]);

    expect($worker->effectiveEnvironment())->toBe([]);
});

test('effective environment includes tooling when installed', function () {
    $this->isolatedSite->isolatedUser->setToolingVersion('node', '22');

    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->isolatedSite->id,
        'user' => 'isolated-foo',
        'command' => 'php artisan queue:work',
        'environment' => null,
        'status' => WorkerStatus::RUNNING,
    ]);

    $env = $worker->effectiveEnvironment();

    expect($env)->toHaveKey('PATH');
    $this->assertStringContainsString('/home/isolated-foo/.local/share/mise/shims', $env['PATH']);
});

test('effective environment overlays tooling over user supplied', function () {
    $this->isolatedSite->isolatedUser->setToolingVersion('node', '22');

    $worker = Worker::factory()->withEnvironment(['CUSTOM' => 'value', 'PATH' => '/user/path'])->create([
        'server_id' => $this->server->id,
        'site_id' => $this->isolatedSite->id,
        'user' => 'isolated-foo',
        'command' => 'php artisan queue:work',
        'status' => WorkerStatus::RUNNING,
    ]);

    $env = $worker->effectiveEnvironment();

    expect($env['CUSTOM'])->toBe('value');
    $this->assertStringContainsString('/home/isolated-foo/.local/share/mise/shims', $env['PATH']);
    $this->assertStringNotContainsString('/user/path', $env['PATH']);
});

test('server bound worker skips tooling', function () {
    $this->isolatedSite->isolatedUser->setToolingVersion('node', '22');

    $worker = Worker::factory()->withEnvironment(['CUSTOM' => 'value'])->create([
        'server_id' => $this->server->id,
        'site_id' => null,
        'user' => 'vito',
        'command' => 'php artisan queue:work',
        'status' => WorkerStatus::RUNNING,
    ]);

    expect($worker->effectiveEnvironment())->toBe(['CUSTOM' => 'value']);
});

test('command references detects token uses', function () {
    expect(ToolingRegistry::commandReferences('node app.js', 'node'))->toBeTrue();
    expect(ToolingRegistry::commandReferences('npm run worker', 'node'))->toBeTrue();
    expect(ToolingRegistry::commandReferences('cd /path && node app.js', 'node'))->toBeTrue();
    expect(ToolingRegistry::commandReferences('node', 'node'))->toBeTrue();
    expect(ToolingRegistry::commandReferences('bun run start', 'bun'))->toBeTrue();

    expect(ToolingRegistry::commandReferences('php run-node.sh', 'node'))->toBeFalse();
    expect(ToolingRegistry::commandReferences('nodejs app.js', 'node'))->toBeFalse();
    expect(ToolingRegistry::commandReferences('php artisan queue:work', 'node'))->toBeFalse();
    expect(ToolingRegistry::commandReferences('node-app.js', 'node'))->toBeFalse();
    expect(ToolingRegistry::commandReferences('node app.js', 'rustup'))->toBeFalse();
});

test('refresh rewrites conf for worker on origin site', function () {
    $fake = SSH::fake();

    $this->isolatedSite->isolatedUser->setToolingVersion('node', '22');

    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->isolatedSite->id,
        'user' => 'isolated-foo',
        'command' => 'php artisan queue:work',
        'status' => WorkerStatus::RUNNING,
    ]);

    app(RefreshSiteWorkerConfigs::class)->refresh($this->isolatedSite, 'node');

    $fake->assertExecutedContains("/etc/supervisor/conf.d/{$worker->id}.conf");
    $this->assertStringContainsString(
        '/home/isolated-foo/.local/share/mise/shims',
        $fake->getUploadedContent(),
    );
});

test('refresh propagates to sibling site workers', function () {
    $fake = SSH::fake();

    $sourceControl = SourceControl::factory()->create([
        'provider' => Github::id(),
        'user_id' => $this->user->id,
    ]);

    $sibling = Site::factory()->create([
        'server_id' => $this->server->id,
        'domain' => 'iso-two.test',
        'user' => 'isolated-foo',
        'path' => '/home/isolated-foo/iso-two.test',
        'source_control_id' => $sourceControl->id,
        'type' => Laravel::id(),
        'status' => SiteStatus::READY,
        'type_data' => [],
    ]);

    $this->isolatedSite->isolatedUser->setToolingVersion('node', '22');

    $siblingWorker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $sibling->id,
        'user' => 'isolated-foo',
        'command' => 'php artisan schedule:work',
        'status' => WorkerStatus::RUNNING,
    ]);

    app(RefreshSiteWorkerConfigs::class)->refresh($this->isolatedSite, 'node');

    $fake->assertExecutedContains("/etc/supervisor/conf.d/{$siblingWorker->id}.conf");
    $this->addToAssertionCount(1);
});

test('refresh restarts worker whose command references changed tool', function () {
    $fake = SSH::fake();

    $this->isolatedSite->isolatedUser->setToolingVersion('node', '22');

    Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->isolatedSite->id,
        'user' => 'isolated-foo',
        'command' => 'node app.js',
        'status' => WorkerStatus::RUNNING,
    ]);

    app(RefreshSiteWorkerConfigs::class)->refresh($this->isolatedSite, 'node');

    $fake->assertExecutedContains('supervisorctl restart');
    $this->addToAssertionCount(1);
});

test('refresh does not restart when command unrelated', function () {
    $fake = SSH::fake();

    $this->isolatedSite->isolatedUser->setToolingVersion('node', '22');

    Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->isolatedSite->id,
        'user' => 'isolated-foo',
        'command' => 'php artisan queue:work',
        'status' => WorkerStatus::RUNNING,
    ]);

    app(RefreshSiteWorkerConfigs::class)->refresh($this->isolatedSite, 'node');

    $fake->assertNotExecutedContains('supervisorctl restart');
    $this->addToAssertionCount(1);
});

test('refresh skips workers on sites that do not support tooling', function () {
    $fake = SSH::fake();

    $loadBalancer = Site::factory()->create([
        'server_id' => $this->server->id,
        'domain' => 'lb.test',
        'user' => 'isolated-foo',
        'path' => '/home/isolated-foo/lb.test',
        'type' => LoadBalancer::id(),
        'status' => SiteStatus::READY,
        'type_data' => [],
    ]);

    $this->isolatedSite->isolatedUser->setToolingVersion('node', '22');

    $lbWorker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $loadBalancer->id,
        'user' => 'isolated-foo',
        'command' => 'node app.js',
        'status' => WorkerStatus::RUNNING,
    ]);

    app(RefreshSiteWorkerConfigs::class)->refresh($this->isolatedSite, 'node');

    $fake->assertNotExecutedContains("/etc/supervisor/conf.d/{$lbWorker->id}.conf");
    $this->addToAssertionCount(1);
});
