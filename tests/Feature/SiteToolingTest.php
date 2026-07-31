<?php

use App\DTOs\DynamicField;
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
use App\SiteTypes\NodeSite;
use App\SiteTypes\PHPBlank;
use App\SiteTypes\PHPMyAdmin;
use App\SiteTypes\PHPSite;
use App\SiteTypes\Wordpress;
use App\SourceControlProviders\Github;
use App\Tooling\NodeTooling;
use App\Tooling\PnpmTooling;
use App\Tooling\SiteToolingState;
use App\Tooling\ToolingRegistry;
use App\Tooling\YarnTooling;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function () {
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
});

function vitoPestFeatureSiteToolingTestIuser(): IsolatedUser
{
    return test()->isolatedSite->isolatedUser()->firstOrFail();
}

test('failed site is forbidden', function () {
    $this->isolatedSite->update(['status' => SiteStatus::INSTALLATION_FAILED]);

    $this->actingAs($this->user);

    $this->get(route('site-tooling', ['server' => $this->server, 'site' => $this->isolatedSite]))
        ->assertForbidden();

    $this->post(route('site-tooling.install', ['server' => $this->server, 'site' => $this->isolatedSite, 'tool' => 'node']), [
        'version' => '22',
    ])->assertForbidden();
});

test('index renders for isolated site', function () {
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
});

test('non isolated site is forbidden', function () {
    $this->actingAs($this->user);

    $this->get(route('site-tooling', ['server' => $this->server, 'site' => $this->site]))
        ->assertForbidden();

    $this->post(route('site-tooling.install', ['server' => $this->server, 'site' => $this->site, 'tool' => 'node']), [
        'version' => '22',
    ])->assertForbidden();

    $this->delete(route('site-tooling.uninstall', ['server' => $this->server, 'site' => $this->site, 'tool' => 'node']))
        ->assertForbidden();
});

test('install dispatches and propagates to siblings', function () {
    SSH::fake();
    $this->actingAs($this->user);

    $this->post(route('site-tooling.install', ['server' => $this->server, 'site' => $this->isolatedSite, 'tool' => 'node']), [
        'version' => '24',
    ])->assertRedirect();

    SSH::assertExecutedContains('mise use -g node@24');

    $iuser = vitoPestFeatureSiteToolingTestIuser();
    expect($iuser->toolingVersion('node'))->toBe('24');
    expect($this->sibling->refresh()->isolatedUser?->toolingVersion('node'))->toBe('24');
});

test('install pnpm works for new tool', function () {
    SSH::fake();
    $this->actingAs($this->user);

    $this->post(route('site-tooling.install', ['server' => $this->server, 'site' => $this->isolatedSite, 'tool' => 'pnpm']), [
        'version' => '9',
    ])->assertRedirect();

    SSH::assertExecutedContains('mise use -g pnpm@9');

    $iuser = vitoPestFeatureSiteToolingTestIuser();
    expect($iuser->toolingVersion('pnpm'))->toBe('9');
});

test('uninstall removes tool entry from iuser', function () {
    SSH::fake();

    vitoPestFeatureSiteToolingTestIuser()->setToolingVersion('node', '22');

    $this->actingAs($this->user);

    $this->delete(route('site-tooling.uninstall', ['server' => $this->server, 'site' => $this->isolatedSite, 'tool' => 'node']))
        ->assertRedirect();

    SSH::assertExecutedContains('mise unuse -g node');
    SSH::assertExecutedContains('mise uninstall node --all');

    $iuser = vitoPestFeatureSiteToolingTestIuser();
    expect($iuser->toolingVersion('node'))->toBeNull();
    expect($iuser->toolingStatus('node'))->toBeNull();
    $this->assertArrayNotHasKey('node', $iuser->installed_tooling ?? []);
});

test('install validation rejects unsupported version', function () {
    SSH::fake();
    $this->actingAs($this->user);

    $this->post(route('site-tooling.install', ['server' => $this->server, 'site' => $this->isolatedSite, 'tool' => 'node']), [
        'version' => '99',
    ])->assertSessionHasErrors('version');

    expect(vitoPestFeatureSiteToolingTestIuser()->toolingVersion('node'))->toBeNull();
});

test('unknown tool returns 404', function () {
    $this->actingAs($this->user);

    $this->post(route('site-tooling.install', ['server' => $this->server, 'site' => $this->isolatedSite, 'tool' => 'rustup']), [
        'version' => '1',
    ])->assertNotFound();
});

test('unauthenticated request is redirected', function () {
    $this->get(route('site-tooling', ['server' => $this->server, 'site' => $this->isolatedSite]))
        ->assertRedirect();
});

test('user from another project is forbidden', function () {
    $otherUser = User::factory()->create();
    $otherUser->ensureHasDefaultProject();

    $this->actingAs($otherUser);

    $this->get(route('site-tooling', ['server' => $this->server, 'site' => $this->isolatedSite]))
        ->assertForbidden();
});

test('lock runtime versions now covers pnpm and yarn', function () {
    SSH::fake();

    vitoPestFeatureSiteToolingTestIuser()->setToolingVersion('node', '22');

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
});

test('only sibling sites with mise trait are updated', function () {
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

    expect(vitoPestFeatureSiteToolingTestIuser()->toolingVersion('node'))->toBe('22');
});

test('installing status blocks concurrent install', function () {
    SSH::fake();

    vitoPestFeatureSiteToolingTestIuser()->setToolingStatus('node', SiteToolingState::STATUS_INSTALLING);

    $this->actingAs($this->user);

    $this->post(route('site-tooling.install', ['server' => $this->server, 'site' => $this->isolatedSite, 'tool' => 'node']), [
        'version' => '24',
    ])->assertSessionHasErrors('tool');
});

test('failed install records failed status', function () {
    $job = new InstallSiteToolingJob($this->isolatedSite, 'node', '24');

    $job->failed(new RuntimeException('boom'));

    $iuser = vitoPestFeatureSiteToolingTestIuser();
    expect($iuser->toolingStatus('node'))->toBe('install_failed');
});

test('failed uninstall records failed status', function () {
    vitoPestFeatureSiteToolingTestIuser()->setToolingVersion('node', '22');

    $job = new UninstallSiteToolingJob($this->isolatedSite, 'node');

    $job->failed(new RuntimeException('boom'));

    $iuser = vitoPestFeatureSiteToolingTestIuser();
    expect($iuser->toolingStatus('node'))->toBe('uninstall_failed');
});

test('install marks failed when tool missing from registry', function () {
    Event::fake([SocketEvent::class]);

    vitoPestFeatureSiteToolingTestIuser()->setToolingStatus('ghost-tool', SiteToolingState::STATUS_INSTALLING);

    dispatch_sync(new InstallSiteToolingJob($this->isolatedSite, 'ghost-tool', '1.0'));

    expect(vitoPestFeatureSiteToolingTestIuser()->toolingStatus('ghost-tool'))->toBe('install_failed');

    Event::assertDispatched(
        SocketEvent::class,
        fn (SocketEvent $event) => $event->data->type === 'isolated-user.tooling-updated',
    );
});

test('uninstall marks failed when tool missing from registry', function () {
    Event::fake([SocketEvent::class]);

    vitoPestFeatureSiteToolingTestIuser()->setToolingStatus('ghost-tool', SiteToolingState::STATUS_UNINSTALLING);

    dispatch_sync(new UninstallSiteToolingJob($this->isolatedSite, 'ghost-tool'));

    expect(vitoPestFeatureSiteToolingTestIuser()->toolingStatus('ghost-tool'))->toBe('uninstall_failed');

    Event::assertDispatched(
        SocketEvent::class,
        fn (SocketEvent $event) => $event->data->type === 'isolated-user.tooling-updated',
    );
});

test('complete install broadcasts tooling updated', function () {
    Event::fake([SocketEvent::class]);

    SiteToolingState::completeInstall($this->isolatedSite, 'node', '22');

    Event::assertDispatched(
        SocketEvent::class,
        fn (SocketEvent $event) => $event->data->type === 'isolated-user.tooling-updated'
            && $event->data->data['id'] === vitoPestFeatureSiteToolingTestIuser()->id,
    );
});

test('complete uninstall broadcasts tooling updated', function () {
    vitoPestFeatureSiteToolingTestIuser()->setToolingVersion('node', '22');

    Event::fake([SocketEvent::class]);

    SiteToolingState::completeUninstall($this->isolatedSite, 'node');

    Event::assertDispatched(
        SocketEvent::class,
        fn (SocketEvent $event) => $event->data->type === 'isolated-user.tooling-updated'
            && $event->data->data['id'] === vitoPestFeatureSiteToolingTestIuser()->id,
    );
});

test('successful install clears status', function () {
    SSH::fake();
    $this->actingAs($this->user);

    $this->post(route('site-tooling.install', ['server' => $this->server, 'site' => $this->isolatedSite, 'tool' => 'node']), [
        'version' => '22',
    ])->assertRedirect();

    $iuser = vitoPestFeatureSiteToolingTestIuser();
    expect($iuser->toolingStatus('node'))->toBeNull();
    expect($iuser->toolingVersion('node'))->toBe('22');
});

test('setup requested tooling installs each requested tool and strips type data', function () {
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

    expect($iuser->toolingVersion('node'))->toBe('22');
    expect($iuser->toolingVersion('pnpm'))->toBe('9');
    expect($iuser->toolingVersion('yarn'))->toBeNull();

    $site->refresh();
    $this->assertArrayNotHasKey('node_version', $site->type_data);
    $this->assertArrayNotHasKey('pnpm_version', $site->type_data);
});

test('index exposes required tooling from sibling site types', function () {
    Site::factory()->create([
        'server_id' => $this->server->id,
        'domain' => 'iso-node.test',
        'user' => 'isolated-foo',
        'path' => '/home/isolated-foo/iso-node.test',
        'type' => NodeSite::id(),
        'status' => SiteStatus::READY,
        'type_data' => [],
    ]);

    $this->actingAs($this->user);

    $this->get(route('site-tooling', ['server' => $this->server, 'site' => $this->isolatedSite]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $assert) => $assert
            ->where('required_tooling.node', 'Node.js')
            ->missing('required_tooling.bun')
        );
});

test('uninstall rejected for required tooling', function () {
    SSH::fake();

    Site::factory()->create([
        'server_id' => $this->server->id,
        'domain' => 'iso-node.test',
        'user' => 'isolated-foo',
        'path' => '/home/isolated-foo/iso-node.test',
        'type' => NodeSite::id(),
        'status' => SiteStatus::READY,
        'type_data' => [],
    ]);

    vitoPestFeatureSiteToolingTestIuser()->setToolingVersion('node', '22');

    $this->actingAs($this->user);

    $this->delete(route('site-tooling.uninstall', ['server' => $this->server, 'site' => $this->isolatedSite, 'tool' => 'node']))
        ->assertSessionHasErrors('tool');

    SSH::assertNotExecutedContains('mise unuse -g node');
    expect(vitoPestFeatureSiteToolingTestIuser()->toolingVersion('node'))->toBe('22');
});

test('composer tool is registered', function () {
    $composer = ToolingRegistry::find('composer');

    expect($composer)->not->toBeNull();
    expect($composer::commands())->toBe(['composer']);
    expect($composer::supportedVersions())->toBe(['2']);
});

test('bootstrap exposes composer descriptor', function () {
    $this->actingAs($this->user);

    $this->getJson(route('bootstrap.show'))
        ->assertOk()
        ->assertJsonPath('configs.tooling', fn (array $tooling): bool => in_array('composer', array_column($tooling, 'id'), true));
});

test('composer required for php types but not wordpress or phpmyadmin', function () {
    foreach ([PHPSite::class, Laravel::class, PHPBlank::class] as $type) {
        expect($type::createTimeTools())->toContain('composer');
        expect($type::requiredTooling())->toContain('composer');
    }

    foreach ([Wordpress::class, PHPMyAdmin::class] as $type) {
        expect($type::createTimeTools())->toBe([]);
        expect($type::requiredTooling())->toBe([]);
    }
});

test('composer installs at latest without a create form picker', function () {
    $data = (new Laravel(new Site(['type' => Laravel::id()])))->data([]);
    expect($data['composer_version'])->toBe('2');

    $selector = collect(config('site.types.php.form'))->firstWhere('name', 'package_manager');
    expect($selector['options'])->not->toContain('composer');
});

test('php and php blank package manager defaults to none', function () {
    foreach (['php', 'php-blank'] as $typeId) {
        $selector = collect(config('site.types.'.$typeId.'.form'))->firstWhere('name', 'package_manager');
        expect($selector['default'])->toBe('none');
        expect($selector['options'])->toContain('none');
    }

    $laravel = collect(config('site.types.laravel.form'))->firstWhere('name', 'package_manager');
    expect($laravel['default'])->toBe('node');
    expect($laravel['options'])->not->toContain('none');
});

test('php with no package manager installs only composer', function () {
    $data = (new PHPSite(new Site(['type' => 'php'])))->data([]);

    expect($data['node_version'])->toBe('none');
    expect($data['pnpm_version'])->toBe('none');
    expect($data['yarn_version'])->toBe('none');
    expect($data['composer_version'])->toBe('2');
});

test('tooling selector supports explicit default', function () {
    $explicit = DynamicField::make('package_manager')->toolingSelector(
        [NodeTooling::class, PnpmTooling::class, YarnTooling::class],
        [NodeTooling::class => 'npm'],
        PnpmTooling::class,
    )->toArray();

    expect($explicit['type'])->toBe('tooling-selector');
    expect($explicit['default'])->toBe('pnpm');
    expect($explicit['optionLabels']['node'])->toBe('npm');

    $implicit = DynamicField::make('package_manager')->toolingSelector(
        [YarnTooling::class, PnpmTooling::class],
    )->toArray();

    expect($implicit['default'])->toBe('yarn');
});

test('laravel package manager drives tooling versions', function () {
    $type = new Laravel(new Site(['type' => Laravel::id()]));

    $npm = $type->data(['package_manager' => 'node', 'node_version' => '22']);
    expect($npm['node_version'])->toBe('22');
    expect($npm['pnpm_version'])->toBe('none');
    expect($npm['yarn_version'])->toBe('none');
    expect($npm['composer_version'])->toBe('2');

    $pnpm = $type->data(['package_manager' => 'pnpm', 'pnpm_version' => '9']);
    expect($pnpm['pnpm_version'])->toBe('9');
    expect($pnpm['yarn_version'])->toBe('none');
    expect($pnpm['node_version'])->toBe('none');

    $none = $type->data(['package_manager' => 'none']);
    expect($none['node_version'])->toBe('none');
    expect($none['pnpm_version'])->toBe('none');
    expect($none['yarn_version'])->toBe('none');

    expect(Laravel::createTimeTools())->toBe(['node', 'pnpm', 'yarn', 'composer']);
});

test('install composer installs locally', function () {
    SSH::fake();
    $this->actingAs($this->user);

    $this->post(route('site-tooling.install', ['server' => $this->server, 'site' => $this->isolatedSite, 'tool' => 'composer']), [
        'version' => '2',
    ])->assertRedirect();

    SSH::assertExecutedContains('composer-setup.php');
    SSH::assertExecutedContains('.local/vito/bin');
    SSH::assertExecutedContains('$HOME/.bashrc');

    expect(vitoPestFeatureSiteToolingTestIuser()->toolingVersion('composer'))->toBe('2');
});

test('uninstall composer removes binary and path activation', function () {
    SSH::fake();

    $site = Site::factory()->create([
        'server_id' => $this->server->id,
        'domain' => 'node-only.test',
        'user' => 'isolated-node',
        'path' => '/home/isolated-node/node-only.test',
        'type' => NodeSite::id(),
        'status' => SiteStatus::READY,
        'type_data' => [],
    ]);

    $site->isolatedUser()->firstOrFail()->setToolingVersion('composer', '2');

    $this->actingAs($this->user);

    $this->delete(route('site-tooling.uninstall', ['server' => $this->server, 'site' => $site, 'tool' => 'composer']))
        ->assertRedirect();

    SSH::assertExecutedContains('rm -f "$HOME/.local/vito/bin/composer"');
    SSH::assertExecutedContains('.bashrc');

    expect($site->isolatedUser()->firstOrFail()->toolingVersion('composer'))->toBeNull();
});

test('uninstall composer rejected on laravel site', function () {
    SSH::fake();

    vitoPestFeatureSiteToolingTestIuser()->setToolingVersion('composer', '2');

    $this->actingAs($this->user);

    $this->delete(route('site-tooling.uninstall', ['server' => $this->server, 'site' => $this->isolatedSite, 'tool' => 'composer']))
        ->assertSessionHasErrors('tool');

    SSH::assertNotExecutedContains('.local/vito/bin/composer');
    expect(vitoPestFeatureSiteToolingTestIuser()->toolingVersion('composer'))->toBe('2');
});

test('other user sites are not touched', function () {
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
    expect($otherUserSite->isolatedUser?->toolingVersion('node'))->toBeNull();
});
