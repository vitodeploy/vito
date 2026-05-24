<?php

namespace Tests\Feature;

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
use App\Support\Testing\SSHFake;
use App\Tooling\ToolingRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkerToolingTest extends TestCase
{
    use RefreshDatabase;

    private Site $isolatedSite;

    protected function setUp(): void
    {
        parent::setUp();

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
    }

    public function test_effective_environment_is_empty_when_no_tooling_installed(): void
    {
        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->isolatedSite->id,
            'user' => 'isolated-foo',
            'command' => 'php artisan queue:work',
            'environment' => null,
            'status' => WorkerStatus::RUNNING,
        ]);

        $this->assertSame([], $worker->effectiveEnvironment());
    }

    public function test_effective_environment_includes_tooling_when_installed(): void
    {
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

        $this->assertArrayHasKey('PATH', $env);
        $this->assertStringContainsString('/home/isolated-foo/.local/share/mise/shims', $env['PATH']);
    }

    public function test_effective_environment_overlays_tooling_over_user_supplied(): void
    {
        $this->isolatedSite->isolatedUser->setToolingVersion('node', '22');

        $worker = Worker::factory()->withEnvironment(['CUSTOM' => 'value', 'PATH' => '/user/path'])->create([
            'server_id' => $this->server->id,
            'site_id' => $this->isolatedSite->id,
            'user' => 'isolated-foo',
            'command' => 'php artisan queue:work',
            'status' => WorkerStatus::RUNNING,
        ]);

        $env = $worker->effectiveEnvironment();

        $this->assertSame('value', $env['CUSTOM']);
        $this->assertStringContainsString('/home/isolated-foo/.local/share/mise/shims', $env['PATH']);
        $this->assertStringNotContainsString('/user/path', $env['PATH']);
    }

    public function test_server_bound_worker_skips_tooling(): void
    {
        $this->isolatedSite->isolatedUser->setToolingVersion('node', '22');

        $worker = Worker::factory()->withEnvironment(['CUSTOM' => 'value'])->create([
            'server_id' => $this->server->id,
            'site_id' => null,
            'user' => 'vito',
            'command' => 'php artisan queue:work',
            'status' => WorkerStatus::RUNNING,
        ]);

        $this->assertSame(['CUSTOM' => 'value'], $worker->effectiveEnvironment());
    }

    public function test_command_references_detects_token_uses(): void
    {
        $this->assertTrue(ToolingRegistry::commandReferences('node app.js', 'node'));
        $this->assertTrue(ToolingRegistry::commandReferences('npm run worker', 'node'));
        $this->assertTrue(ToolingRegistry::commandReferences('cd /path && node app.js', 'node'));
        $this->assertTrue(ToolingRegistry::commandReferences('node', 'node'));
        $this->assertTrue(ToolingRegistry::commandReferences('bun run start', 'bun'));

        $this->assertFalse(ToolingRegistry::commandReferences('php run-node.sh', 'node'));
        $this->assertFalse(ToolingRegistry::commandReferences('nodejs app.js', 'node'));
        $this->assertFalse(ToolingRegistry::commandReferences('php artisan queue:work', 'node'));
        $this->assertFalse(ToolingRegistry::commandReferences('node-app.js', 'node'));
        $this->assertFalse(ToolingRegistry::commandReferences('node app.js', 'rustup'));
    }

    public function test_refresh_rewrites_conf_for_worker_on_origin_site(): void
    {
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
            $this->lastUploadedContent($fake),
        );
    }

    private function lastUploadedContent(SSHFake $fake): string
    {
        $ref = new \ReflectionProperty($fake, 'uploadedContent');

        return (string) $ref->getValue($fake);
    }

    public function test_refresh_propagates_to_sibling_site_workers(): void
    {
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
    }

    public function test_refresh_restarts_worker_whose_command_references_changed_tool(): void
    {
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
    }

    public function test_refresh_does_not_restart_when_command_unrelated(): void
    {
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
    }

    public function test_refresh_skips_workers_on_sites_that_do_not_support_tooling(): void
    {
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
    }
}
