<?php

namespace Tests\Feature;

use App\Enums\SiteStatus;
use App\Events\SocketEvent;
use App\Facades\SSH;
use App\Jobs\Site\Tooling\InstallSiteToolingJob;
use App\Jobs\Site\Tooling\UninstallSiteToolingJob;
use App\Models\IsolatedUser;
use App\Models\Site;
use App\Models\SourceControl;
use App\Models\User;
use App\SiteTypes\AbstractSiteType;
use App\SiteTypes\Laravel;
use App\SiteTypes\LoadBalancer;
use App\SourceControlProviders\Github;
use App\Tooling\SiteToolingState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class SiteToolingTest extends TestCase
{
    use RefreshDatabase;

    private Site $isolatedSite;

    private Site $sibling;

    protected function setUp(): void
    {
        parent::setUp();

        $sourceControl = SourceControl::factory()->create([
            'provider' => Github::id(),
            'user_id' => $this->user->id,
        ]);

        $this->isolatedSite = Site::factory()->create([
            'server_id' => $this->server->id,
            'domain' => 'iso-one.test',
            'user' => 'isolated-foo',
            'path' => '/home/isolated-foo/iso-one.test',
            'source_control_id' => $sourceControl->id,
            'type' => Laravel::id(),
            'status' => SiteStatus::READY,
            'type_data' => [],
        ]);

        $this->sibling = Site::factory()->create([
            'server_id' => $this->server->id,
            'domain' => 'iso-two.test',
            'user' => 'isolated-foo',
            'path' => '/home/isolated-foo/iso-two.test',
            'source_control_id' => $sourceControl->id,
            'type' => Laravel::id(),
            'status' => SiteStatus::READY,
            'type_data' => [],
        ]);
    }

    private function iuser(): IsolatedUser
    {
        return $this->isolatedSite->isolatedUser()->firstOrFail();
    }

    public function test_failed_site_is_forbidden(): void
    {
        $this->isolatedSite->update(['status' => SiteStatus::INSTALLATION_FAILED]);

        $this->actingAs($this->user);

        $this->get(route('site-tooling', ['server' => $this->server, 'site' => $this->isolatedSite]))
            ->assertForbidden();

        $this->post(route('site-tooling.install', ['server' => $this->server, 'site' => $this->isolatedSite, 'tool' => 'node']), [
            'version' => '22',
        ])->assertForbidden();
    }

    public function test_index_renders_for_isolated_site(): void
    {
        $this->actingAs($this->user);

        $this->get(route('site-tooling', ['server' => $this->server, 'site' => $this->isolatedSite]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $assert) => $assert
                ->component('site-tooling/index')
                ->where('isolated_user', 'isolated-foo')
                ->has('sibling_sites', 1)
                ->where('sibling_sites.0.domain', 'iso-two.test')
                ->where('installed_versions.node', null)
                ->where('installed_versions.bun', null)
                ->where('installed_versions.pnpm', null)
                ->where('installed_versions.yarn', null)
            );
    }

    public function test_non_isolated_site_is_forbidden(): void
    {
        $this->actingAs($this->user);

        $this->get(route('site-tooling', ['server' => $this->server, 'site' => $this->site]))
            ->assertForbidden();

        $this->post(route('site-tooling.install', ['server' => $this->server, 'site' => $this->site, 'tool' => 'node']), [
            'version' => '22',
        ])->assertForbidden();

        $this->delete(route('site-tooling.uninstall', ['server' => $this->server, 'site' => $this->site, 'tool' => 'node']))
            ->assertForbidden();
    }

    public function test_install_dispatches_and_propagates_to_siblings(): void
    {
        SSH::fake();
        $this->actingAs($this->user);

        $this->post(route('site-tooling.install', ['server' => $this->server, 'site' => $this->isolatedSite, 'tool' => 'node']), [
            'version' => '24',
        ])->assertRedirect();

        SSH::assertExecutedContains('mise use -g node@24');

        $iuser = $this->iuser();
        $this->assertSame('24', $iuser->toolingVersion('node'));
        $this->assertSame('24', $this->sibling->refresh()->isolatedUser?->toolingVersion('node'));
    }

    public function test_install_pnpm_works_for_new_tool(): void
    {
        SSH::fake();
        $this->actingAs($this->user);

        $this->post(route('site-tooling.install', ['server' => $this->server, 'site' => $this->isolatedSite, 'tool' => 'pnpm']), [
            'version' => '9',
        ])->assertRedirect();

        SSH::assertExecutedContains('mise use -g pnpm@9');

        $iuser = $this->iuser();
        $this->assertSame('9', $iuser->toolingVersion('pnpm'));
    }

    public function test_uninstall_removes_tool_entry_from_iuser(): void
    {
        SSH::fake();

        $this->iuser()->setToolingVersion('node', '22');

        $this->actingAs($this->user);

        $this->delete(route('site-tooling.uninstall', ['server' => $this->server, 'site' => $this->isolatedSite, 'tool' => 'node']))
            ->assertRedirect();

        SSH::assertExecutedContains('mise unuse -g node');
        SSH::assertExecutedContains('mise uninstall node --all');

        $iuser = $this->iuser();
        $this->assertNull($iuser->toolingVersion('node'));
        $this->assertNull($iuser->toolingStatus('node'));
        $this->assertArrayNotHasKey('node', $iuser->installed_tooling ?? []);
    }

    public function test_install_validation_rejects_unsupported_version(): void
    {
        SSH::fake();
        $this->actingAs($this->user);

        $this->post(route('site-tooling.install', ['server' => $this->server, 'site' => $this->isolatedSite, 'tool' => 'node']), [
            'version' => '99',
        ])->assertSessionHasErrors('version');

        $this->assertNull($this->iuser()->toolingVersion('node'));
    }

    public function test_unknown_tool_returns_404(): void
    {
        $this->actingAs($this->user);

        $this->post(route('site-tooling.install', ['server' => $this->server, 'site' => $this->isolatedSite, 'tool' => 'rustup']), [
            'version' => '1',
        ])->assertNotFound();
    }

    public function test_unauthenticated_request_is_redirected(): void
    {
        $this->get(route('site-tooling', ['server' => $this->server, 'site' => $this->isolatedSite]))
            ->assertRedirect();
    }

    public function test_user_from_another_project_is_forbidden(): void
    {
        $otherUser = User::factory()->create();
        $otherUser->ensureHasDefaultProject();

        $this->actingAs($otherUser);

        $this->get(route('site-tooling', ['server' => $this->server, 'site' => $this->isolatedSite]))
            ->assertForbidden();
    }

    public function test_lock_runtime_versions_now_covers_pnpm_and_yarn(): void
    {
        SSH::fake();

        $this->iuser()->setToolingVersion('node', '22');

        $sourceControl = SourceControl::factory()->create([
            'provider' => Github::id(),
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user);

        Http::fake([
            'https://api.github.com/repos/*' => Http::response([], 200),
        ]);

        $this->post(route('sites.store', ['server' => $this->server]), [
            'type' => Laravel::id(),
            'domain' => 'iso-three.test',
            'user' => 'isolated-foo',
            'php_version' => '8.2',
            'web_directory' => 'public',
            'repository' => 'organization/repository',
            'branch' => 'main',
            'source_control' => $sourceControl->id,
            'node_version' => '24',
            'bun_version' => 'none',
        ])->assertSessionHasErrors('node_version');
    }

    public function test_only_sibling_sites_with_mise_trait_are_updated(): void
    {
        SSH::fake();

        Site::factory()->create([
            'server_id' => $this->server->id,
            'domain' => 'lb.test',
            'user' => 'isolated-foo',
            'path' => '/home/isolated-foo/lb.test',
            'type' => LoadBalancer::id(),
            'type_data' => [],
        ]);

        $this->actingAs($this->user);

        $this->post(route('site-tooling.install', ['server' => $this->server, 'site' => $this->isolatedSite, 'tool' => 'node']), [
            'version' => '22',
        ])->assertRedirect();

        $this->assertSame('22', $this->iuser()->toolingVersion('node'));
    }

    public function test_installing_status_blocks_concurrent_install(): void
    {
        SSH::fake();

        $this->iuser()->setToolingStatus('node', SiteToolingState::STATUS_INSTALLING);

        $this->actingAs($this->user);

        $this->post(route('site-tooling.install', ['server' => $this->server, 'site' => $this->isolatedSite, 'tool' => 'node']), [
            'version' => '24',
        ])->assertSessionHasErrors('tool');
    }

    public function test_failed_install_records_failed_status(): void
    {
        $job = new InstallSiteToolingJob($this->isolatedSite, 'node', '24');

        $job->failed(new \RuntimeException('boom'));

        $iuser = $this->iuser();
        $this->assertSame('install_failed', $iuser->toolingStatus('node'));
    }

    public function test_failed_uninstall_records_failed_status(): void
    {
        $this->iuser()->setToolingVersion('node', '22');

        $job = new UninstallSiteToolingJob($this->isolatedSite, 'node');

        $job->failed(new \RuntimeException('boom'));

        $iuser = $this->iuser();
        $this->assertSame('uninstall_failed', $iuser->toolingStatus('node'));
    }

    public function test_complete_install_broadcasts_tooling_updated(): void
    {
        Event::fake([SocketEvent::class]);

        SiteToolingState::completeInstall($this->isolatedSite, 'node', '22');

        Event::assertDispatched(
            SocketEvent::class,
            fn (SocketEvent $event) => $event->data->type === 'isolated-user.tooling-updated'
                && $event->data->data['id'] === $this->iuser()->id,
        );
    }

    public function test_complete_uninstall_broadcasts_tooling_updated(): void
    {
        $this->iuser()->setToolingVersion('node', '22');

        Event::fake([SocketEvent::class]);

        SiteToolingState::completeUninstall($this->isolatedSite, 'node');

        Event::assertDispatched(
            SocketEvent::class,
            fn (SocketEvent $event) => $event->data->type === 'isolated-user.tooling-updated'
                && $event->data->data['id'] === $this->iuser()->id,
        );
    }

    public function test_successful_install_clears_status(): void
    {
        SSH::fake();
        $this->actingAs($this->user);

        $this->post(route('site-tooling.install', ['server' => $this->server, 'site' => $this->isolatedSite, 'tool' => 'node']), [
            'version' => '22',
        ])->assertRedirect();

        $iuser = $this->iuser();
        $this->assertNull($iuser->toolingStatus('node'));
        $this->assertSame('22', $iuser->toolingVersion('node'));
    }

    public function test_setup_requested_tooling_installs_each_requested_tool_and_strips_type_data(): void
    {
        SSH::fake();

        /** @var Site $site */
        $site = Site::factory()->create([
            'server_id' => $this->server->id,
            'domain' => 'iso-three.test',
            'user' => 'isolated-foo',
            'path' => '/home/isolated-foo/iso-three.test',
            'type' => Laravel::id(),
            'status' => SiteStatus::READY,
            'type_data' => [
                'node_version' => '22',
                'pnpm_version' => '9',
                'yarn_version' => 'none',
            ],
        ]);

        $siteType = new class($site) extends AbstractSiteType
        {
            public static function id(): string
            {
                return 'test-setup-requested-tooling';
            }

            public function language(): string
            {
                return 'php';
            }

            public function requiredServices(): array
            {
                return [];
            }

            public function install(): void {}

            public static function make(): self
            {
                return new self(new Site);
            }

            public static function createTimeTools(): array
            {
                return ['node', 'pnpm', 'yarn'];
            }

            public function runSetup(): void
            {
                $this->setupRequestedTooling();
            }
        };

        $siteType->runSetup();

        $iuser = $site->isolatedUser()->firstOrFail();

        $this->assertSame('22', $iuser->toolingVersion('node'));
        $this->assertSame('9', $iuser->toolingVersion('pnpm'));
        $this->assertNull($iuser->toolingVersion('yarn'));

        $site->refresh();
        $this->assertArrayNotHasKey('node_version', $site->type_data);
        $this->assertArrayNotHasKey('pnpm_version', $site->type_data);
    }

    public function test_other_user_sites_are_not_touched(): void
    {
        SSH::fake();

        $otherUserSite = Site::factory()->create([
            'server_id' => $this->server->id,
            'domain' => 'other-user.test',
            'user' => 'isolated-bar',
            'path' => '/home/isolated-bar/other-user.test',
            'type' => Laravel::id(),
            'type_data' => [],
        ]);

        $this->actingAs($this->user);

        $this->post(route('site-tooling.install', ['server' => $this->server, 'site' => $this->isolatedSite, 'tool' => 'node']), [
            'version' => '22',
        ])->assertRedirect();

        $otherUserSite->refresh();
        $this->assertNull($otherUserSite->isolatedUser?->toolingVersion('node'));
    }
}
