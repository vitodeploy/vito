<?php

use App\Enums\SecurityControlStatus;
use App\Enums\ServiceStatus;
use App\Facades\SSH;
use App\Jobs\Server\UpdateJob;
use App\Models\Server;
use App\Services\Fail2ban\Fail2ban;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('see security page', function () {
    $this->actingAs($this->user);

    $this->get(route('security', $this->server))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('security/index'));
});

test('enable auto update', function () {
    $this->actingAs($this->user);

    $this->post(route('security.auto-update', $this->server), [
        'enabled' => true,
        'schedule' => '0 2 * * *',
    ])->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('servers', [
        'id' => $this->server->id,
        'auto_update' => true,
        'auto_update_schedule' => '0 2 * * *',
    ]);
});

test('disable auto update clears schedule', function () {
    $this->actingAs($this->user);
    $this->server->update(['auto_update' => true, 'auto_update_schedule' => '0 2 * * *']);

    $this->post(route('security.auto-update', $this->server), [
        'enabled' => false,
    ])->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('servers', [
        'id' => $this->server->id,
        'auto_update' => false,
        'auto_update_schedule' => null,
    ]);
});

test('invalid schedule is rejected', function () {
    $this->actingAs($this->user);

    $this->post(route('security.auto-update', $this->server), [
        'enabled' => true,
        'schedule' => 'not-a-cron',
    ])->assertSessionHasErrors('schedule');
});

test('disable password authentication', function () {
    SSH::fake();
    $this->actingAs($this->user);

    $this->post(route('security.password-auth', $this->server), [
        'enabled' => false,
    ])->assertSessionDoesntHaveErrors();

    SSH::assertExecutedContains('00-vito-hardening.conf');
    SSH::assertExecutedContains('PasswordAuthentication no');

    $state = $this->server->refresh()->securityState();
    expect($state['password_authentication']['enabled'])->toBeFalse();
    expect($state['password_authentication']['status'])->toBe(SecurityControlStatus::READY->value);
});

test('disable root login', function () {
    SSH::fake();
    $this->actingAs($this->user);

    $this->server->update(['ssh_user' => 'vito']);

    $this->post(route('security.root-login', $this->server), [
        'enabled' => false,
    ])->assertSessionDoesntHaveErrors();

    SSH::assertExecutedContains('00-vito-root-login.conf');
    SSH::assertExecutedContains('PermitRootLogin no');

    $state = $this->server->refresh()->securityState();
    expect($state['root_login']['enabled'])->toBeFalse();
    expect($state['root_login']['status'])->toBe(SecurityControlStatus::READY->value);
});

test('disabling root login removes root from ssh login users', function () {
    $this->actingAs($this->user);
    $this->server->update(['ssh_user' => 'vito']);

    expect($this->server->sshLoginUsers())->toContain('root');

    $this->server->jsonUpdate('feature_data', 'security', ['root_login' => ['enabled' => false]]);
    $server = $this->server->refresh();

    expect($server->sshLoginUsers())->not->toContain('root');
    expect($server->sshLoginUsers())->toContain('vito');
    expect($server->getSshUsers())->toContain('root');
});

test('cannot disable root login when vito connects as root', function () {
    $ssh = SSH::fake();
    $this->actingAs($this->user);

    $this->server->update(['ssh_user' => 'root']);

    $this->post(route('security.root-login', $this->server), [
        'enabled' => false,
    ])->assertSessionHasErrors('enabled');

    $ssh->assertNotExecutedContains('PermitRootLogin no');
    expect($this->server->refresh()->securityState()['root_login']['enabled'])->toBeTrue();
});

test('score counts controls only when complete', function () {
    $this->actingAs($this->user);

    $passwordCheck = fn () => collect($this->server->refresh()->securityScore()['checks'])->firstWhere('key', 'password_auth')['passed'];

    $this->server->jsonUpdate('feature_data', 'security', [
        'password_authentication' => ['enabled' => false, 'status' => SecurityControlStatus::UPDATING->value],
    ]);
    expect($passwordCheck())->toBeFalse('In-progress changes must not count toward the score.');

    $this->server->jsonUpdate('feature_data', 'security', [
        'password_authentication' => ['enabled' => false, 'status' => SecurityControlStatus::READY->value],
    ]);
    expect($passwordCheck())->toBeTrue('Completed changes count toward the score.');
});

test('firewall scores only when ready', function () {
    $this->actingAs($this->user);

    $firewallCheck = fn () => collect($this->server->refresh()->securityScore()['checks'])->firstWhere('key', 'firewall')['passed'];

    $this->server->firewall()->update(['status' => ServiceStatus::INSTALLING]);
    expect($firewallCheck())->toBeFalse();

    $this->server->firewall()->update(['status' => ServiceStatus::READY]);
    expect($firewallCheck())->toBeTrue();
});

test('root login excluded from score for root user servers', function () {
    $this->actingAs($this->user);

    $this->server->update(['ssh_user' => 'vito']);
    expect($this->server->refresh()->securityScore()['total'])->toBe(5);

    $this->server->update(['ssh_user' => 'root']);
    $score = $this->server->refresh()->securityScore();
    expect($score['total'])->toBe(4);
    expect(collect($score['checks'])->firstWhere('key', 'root_login'))->toBeNull();
});

test('install fail2ban', function () {
    SSH::fake();
    $this->actingAs($this->user);

    $this->post(route('security.fail2ban.install', $this->server), [
        'maxretry' => 3,
        'bantime' => '1h',
    ])->assertSessionDoesntHaveErrors();

    SSH::assertExecutedContains('apt-get install -y fail2ban');
    SSH::assertExecutedContains('backend = systemd');
    SSH::assertExecutedContains('jail.d/vito.local');

    $this->assertDatabaseHas('services', [
        'server_id' => $this->server->id,
        'name' => 'fail2ban',
        'type' => 'fail2ban',
        'status' => ServiceStatus::READY,
    ]);
});

test('fail2ban rejects shell injection in ignoreip', function () {
    SSH::fake();
    $this->actingAs($this->user);

    $this->post(route('security.fail2ban.install', $this->server), [
        'ignoreip' => '127.0.0.1 $(touch /tmp/pwned)',
    ])->assertSessionHasErrors('ignoreip');

    $this->assertDatabaseMissing('services', [
        'server_id' => $this->server->id,
        'name' => 'fail2ban',
    ]);
});

test('fail2ban rejects newline injection in bantime', function () {
    SSH::fake();
    $this->actingAs($this->user);

    $service = $this->server->services()->create([
        'type' => Fail2ban::type(),
        'name' => Fail2ban::id(),
        'version' => 'latest',
        'status' => ServiceStatus::READY,
    ]);

    $this->patch(route('security.fail2ban.update', $this->server), [
        'bantime' => "10m\nenabled",
    ])->assertSessionHasErrors('bantime');

    expect($service->fresh())->not->toBeNull();
});

test('uninstall fail2ban', function () {
    SSH::fake();
    $this->actingAs($this->user);

    $service = $this->server->services()->create([
        'type' => Fail2ban::type(),
        'name' => Fail2ban::id(),
        'version' => 'latest',
        'status' => ServiceStatus::READY,
    ]);

    $this->delete(route('security.fail2ban.destroy', $this->server))
        ->assertSessionDoesntHaveErrors();

    SSH::assertExecutedContains('purge -y fail2ban');
    $this->assertDatabaseMissing('services', ['id' => $service->id]);
});

test('install firewall', function () {
    SSH::fake();
    $this->actingAs($this->user);

    $this->server->services()->where('type', 'firewall')->delete();
    expect($this->server->refresh()->firewall())->toBeNull();

    $this->post(route('security.firewall.install', $this->server))
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('services', [
        'server_id' => $this->server->id,
        'name' => 'ufw',
        'type' => 'firewall',
        'status' => ServiceStatus::READY,
    ]);
});

test('security score reflects applied controls', function () {
    $this->actingAs($this->user);

    $score = $this->server->securityScore();
    expect($score['total'])->toBe(5);
    $firewallCheck = collect($score['checks'])->firstWhere('key', 'firewall');
    expect($firewallCheck['passed'])->toBeTrue();

    $this->server->update(['auto_update' => true]);
    $this->server->jsonUpdate('feature_data', 'security', [
        'password_authentication' => ['enabled' => false],
    ]);

    $after = $this->server->refresh()->securityScore();
    expect($after['score'])->toBeGreaterThan($score['score']);
});

test('auto update runner dispatches only due servers', function () {
    Queue::fake();
    $this->travelTo(now()->startOfDay()->setTime(3, 0));

    $this->server->update(['status' => 'ready', 'auto_update' => true, 'auto_update_schedule' => '0 3 * * *']);

    $notDue = Server::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
        'status' => 'ready',
        'auto_update' => true,
        'auto_update_schedule' => '0 4 * * *',
    ]);

    $this->artisan('servers:auto-update')->assertSuccessful();

    Queue::assertPushed(UpdateJob::class, 1);
    Queue::assertPushed(UpdateJob::class, fn (UpdateJob $job) => vitoPestFeatureSecurityTestJobServerId($job) === $this->server->id);
    Queue::assertNotPushed(UpdateJob::class, fn (UpdateJob $job) => vitoPestFeatureSecurityTestJobServerId($job) === $notDue->id);
});

function vitoPestFeatureSecurityTestJobServerId(UpdateJob $job): int
{
    $reflection = new ReflectionClass($job);
    $property = $reflection->getProperty('server');

    /** @var Server $server */
    $server = $property->getValue($job);

    return $server->id;
}
