<?php

namespace Tests\Feature;

use App\Enums\DeploymentStatus;
use App\Enums\WorkerStatus;
use App\Events\SocketEvent;
use App\Facades\SSH;
use App\Models\Deployment;
use App\Models\Site;
use App\Models\Worker;
use App\SiteTypes\NodeSite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class SiteSettingsProxiedSiteTest extends TestCase
{
    use RefreshDatabase;

    private Site $proxiedSite;

    protected function setUp(): void
    {
        parent::setUp();

        $this->proxiedSite = Site::factory()->create([
            'server_id' => $this->server->id,
            'user' => 'isolated-foo',
            'path' => '/home/isolated-foo/app.test',
            'type' => NodeSite::id(),
            'port' => 3000,
            'type_data' => [
                'node_version' => '22',
                'package_manager' => 'npm',
                'start_command' => 'npm start',
            ],
        ]);

        $this->actingAs($this->user);
    }

    public function test_settings_page_exposes_proxied_site_flags(): void
    {
        $this->get(route('site-settings', ['server' => $this->server, 'site' => $this->proxiedSite]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $a) => $a
                ->component('site-settings/index')
                ->where('site.is_proxied_site_type', true)
                ->where('site.start_command', 'npm start')
                ->where('site.port', 3000)
            );
    }

    public function test_update_port_updates_site_and_regenerates_vhost(): void
    {
        SSH::fake();
        Event::fake([SocketEvent::class]);

        $this->patch(route('site-settings.update-port', ['server' => $this->server, 'site' => $this->proxiedSite]), [
            'port' => 4000,
        ])->assertRedirect();

        $this->proxiedSite->refresh();
        $this->assertSame(4000, $this->proxiedSite->port);

        Event::assertDispatched(SocketEvent::class, fn (SocketEvent $event) => $event->data->type === 'site.updated');
    }

    public function test_update_port_validates(): void
    {
        SSH::fake();

        $this->patch(route('site-settings.update-port', ['server' => $this->server, 'site' => $this->proxiedSite]), [
            'port' => 99999,
        ])->assertSessionHasErrors('port');
    }

    public function test_update_start_command_rejects_newline_injection(): void
    {
        SSH::fake();

        $this->patch(route('site-settings.update-start-command', ['server' => $this->server, 'site' => $this->proxiedSite]), [
            'start_command' => "npm start\nuser=root",
        ])->assertSessionHasErrors('start_command');

        $this->patch(route('site-settings.update-start-command', ['server' => $this->server, 'site' => $this->proxiedSite]), [
            'start_command' => "npm start\rcommand=/bin/sh",
        ])->assertSessionHasErrors('start_command');

        $this->proxiedSite->refresh();
        $this->assertSame('npm start', $this->proxiedSite->type_data['start_command']);
    }

    public function test_update_start_command_pre_first_deploy_stores_only(): void
    {
        SSH::fake();

        $this->patch(route('site-settings.update-start-command', ['server' => $this->server, 'site' => $this->proxiedSite]), [
            'start_command' => 'pnpm start',
        ])->assertRedirect()
            ->assertSessionHas('info');

        $this->proxiedSite->refresh();
        $this->assertSame('pnpm start', $this->proxiedSite->type_data['start_command']);
        $this->assertNull($this->proxiedSite->workers()->where('name', 'app')->first());
    }

    public function test_update_start_command_with_existing_worker_updates_conf_no_restart(): void
    {
        $fake = SSH::fake();

        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->proxiedSite->id,
            'user' => 'isolated-foo',
            'name' => 'app',
            'command' => 'npm start',
            'status' => WorkerStatus::RUNNING,
        ]);
        $this->proxiedSite->jsonUpdate('type_data', 'bootstrap_worker_id', $worker->id);

        $this->patch(route('site-settings.update-start-command', ['server' => $this->server, 'site' => $this->proxiedSite]), [
            'start_command' => 'pnpm start',
        ])->assertRedirect()
            ->assertSessionHas('warning');

        $worker->refresh();
        $this->assertSame('pnpm start', $worker->command);

        $fake->assertNotExecutedContains('supervisorctl restart');
    }

    public function test_update_start_command_with_restart_rewrites_config_and_restarts(): void
    {
        $fake = SSH::fake();

        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->proxiedSite->id,
            'user' => 'isolated-foo',
            'name' => 'app',
            'command' => 'npm start',
            'status' => WorkerStatus::RUNNING,
        ]);
        $this->proxiedSite->jsonUpdate('type_data', 'bootstrap_worker_id', $worker->id);

        $this->patch(route('site-settings.update-start-command', ['server' => $this->server, 'site' => $this->proxiedSite]), [
            'start_command' => 'pnpm start',
            'restart' => true,
        ])->assertRedirect()
            ->assertSessionHas('info');

        $worker->refresh();
        $this->assertSame('pnpm start', $worker->command);

        $fake->assertExecutedContains("/etc/supervisor/conf.d/{$worker->id}.conf");
        $fake->assertExecutedContains("supervisorctl restart {$worker->id}:*");
    }

    public function test_needs_first_deploy_warning_present_until_finished_deployment(): void
    {
        $beforeDeploy = $this->get(route('site-settings', ['server' => $this->server, 'site' => $this->proxiedSite]));
        $beforeDeploy->assertInertia(fn (AssertableInertia $a) => $a
            ->where('site.warnings', fn ($warnings) => collect($warnings)->contains(fn ($w) => $w['key'] === 'needs_first_deploy'))
        );

        Deployment::factory()->create([
            'site_id' => $this->proxiedSite->id,
            'status' => DeploymentStatus::FINISHED,
        ]);

        $afterDeploy = $this->get(route('site-settings', ['server' => $this->server, 'site' => $this->proxiedSite]));
        $afterDeploy->assertInertia(fn (AssertableInertia $a) => $a
            ->where('site.warnings', fn ($warnings) => ! collect($warnings)->contains(fn ($w) => $w['key'] === 'needs_first_deploy'))
        );
    }
}
