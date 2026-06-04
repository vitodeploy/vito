<?php

namespace Tests\Feature;

use App\Enums\SecurityControlStatus;
use App\Enums\ServiceStatus;
use App\Facades\SSH;
use App\Jobs\Server\UpdateJob;
use App\Models\Server;
use App\Services\Fail2ban\Fail2ban;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_security_page(): void
    {
        $this->actingAs($this->user);

        $this->get(route('security', $this->server))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('security/index'));
    }

    public function test_enable_auto_update(): void
    {
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
    }

    public function test_disable_auto_update_clears_schedule(): void
    {
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
    }

    public function test_invalid_schedule_is_rejected(): void
    {
        $this->actingAs($this->user);

        $this->post(route('security.auto-update', $this->server), [
            'enabled' => true,
            'schedule' => 'not-a-cron',
        ])->assertSessionHasErrors('schedule');
    }

    public function test_disable_password_authentication(): void
    {
        SSH::fake();
        $this->actingAs($this->user);

        $this->post(route('security.password-auth', $this->server), [
            'enabled' => false,
        ])->assertSessionDoesntHaveErrors();

        SSH::assertExecutedContains('00-vito-hardening.conf');
        SSH::assertExecutedContains('PasswordAuthentication no');

        $state = $this->server->refresh()->securityState();
        $this->assertFalse($state['password_authentication']['enabled']);
        $this->assertSame(SecurityControlStatus::READY->value, $state['password_authentication']['status']);
    }

    public function test_disable_root_login(): void
    {
        SSH::fake();
        $this->actingAs($this->user);

        $this->server->update(['ssh_user' => 'vito']);

        $this->post(route('security.root-login', $this->server), [
            'enabled' => false,
        ])->assertSessionDoesntHaveErrors();

        SSH::assertExecutedContains('00-vito-root-login.conf');
        SSH::assertExecutedContains('PermitRootLogin no');

        $state = $this->server->refresh()->securityState();
        $this->assertFalse($state['root_login']['enabled']);
        $this->assertSame(SecurityControlStatus::READY->value, $state['root_login']['status']);
    }

    public function test_disabling_root_login_removes_root_from_ssh_login_users(): void
    {
        $this->actingAs($this->user);
        $this->server->update(['ssh_user' => 'vito']);

        $this->assertContains('root', $this->server->sshLoginUsers());

        $this->server->jsonUpdate('feature_data', 'security', ['root_login' => ['enabled' => false]]);
        $server = $this->server->refresh();

        $this->assertNotContains('root', $server->sshLoginUsers());
        $this->assertContains('vito', $server->sshLoginUsers());
        $this->assertContains('root', $server->getSshUsers());
    }

    public function test_cannot_disable_root_login_when_vito_connects_as_root(): void
    {
        $ssh = SSH::fake();
        $this->actingAs($this->user);

        $this->server->update(['ssh_user' => 'root']);

        $this->post(route('security.root-login', $this->server), [
            'enabled' => false,
        ])->assertSessionHasErrors('enabled');

        $ssh->assertNotExecutedContains('PermitRootLogin no');
        $this->assertTrue($this->server->refresh()->securityState()['root_login']['enabled']);
    }

    public function test_score_counts_controls_only_when_complete(): void
    {
        $this->actingAs($this->user);

        $passwordCheck = fn () => collect($this->server->refresh()->securityScore()['checks'])->firstWhere('key', 'password_auth')['passed'];

        $this->server->jsonUpdate('feature_data', 'security', [
            'password_authentication' => ['enabled' => false, 'status' => SecurityControlStatus::UPDATING->value],
        ]);
        $this->assertFalse($passwordCheck(), 'In-progress changes must not count toward the score.');

        $this->server->jsonUpdate('feature_data', 'security', [
            'password_authentication' => ['enabled' => false, 'status' => SecurityControlStatus::READY->value],
        ]);
        $this->assertTrue($passwordCheck(), 'Completed changes count toward the score.');
    }

    public function test_firewall_scores_only_when_ready(): void
    {
        $this->actingAs($this->user);

        $firewallCheck = fn () => collect($this->server->refresh()->securityScore()['checks'])->firstWhere('key', 'firewall')['passed'];

        $this->server->firewall()->update(['status' => ServiceStatus::INSTALLING]);
        $this->assertFalse($firewallCheck());

        $this->server->firewall()->update(['status' => ServiceStatus::READY]);
        $this->assertTrue($firewallCheck());
    }

    public function test_root_login_excluded_from_score_for_root_user_servers(): void
    {
        $this->actingAs($this->user);

        $this->server->update(['ssh_user' => 'vito']);
        $this->assertSame(5, $this->server->refresh()->securityScore()['total']);

        $this->server->update(['ssh_user' => 'root']);
        $score = $this->server->refresh()->securityScore();
        $this->assertSame(4, $score['total']);
        $this->assertNull(collect($score['checks'])->firstWhere('key', 'root_login'));
    }

    public function test_install_fail2ban(): void
    {
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
    }

    public function test_fail2ban_rejects_shell_injection_in_ignoreip(): void
    {
        SSH::fake();
        $this->actingAs($this->user);

        $this->post(route('security.fail2ban.install', $this->server), [
            'ignoreip' => '127.0.0.1 $(touch /tmp/pwned)',
        ])->assertSessionHasErrors('ignoreip');

        $this->assertDatabaseMissing('services', [
            'server_id' => $this->server->id,
            'name' => 'fail2ban',
        ]);
    }

    public function test_fail2ban_rejects_newline_injection_in_bantime(): void
    {
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

        $this->assertNotNull($service->fresh());
    }

    public function test_uninstall_fail2ban(): void
    {
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
    }

    public function test_install_firewall(): void
    {
        SSH::fake();
        $this->actingAs($this->user);

        $this->server->services()->where('type', 'firewall')->delete();
        $this->assertNull($this->server->refresh()->firewall());

        $this->post(route('security.firewall.install', $this->server))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('services', [
            'server_id' => $this->server->id,
            'name' => 'ufw',
            'type' => 'firewall',
            'status' => ServiceStatus::READY,
        ]);
    }

    public function test_security_score_reflects_applied_controls(): void
    {
        $this->actingAs($this->user);

        $score = $this->server->securityScore();
        $this->assertSame(5, $score['total']);
        $firewallCheck = collect($score['checks'])->firstWhere('key', 'firewall');
        $this->assertTrue($firewallCheck['passed']);

        $this->server->update(['auto_update' => true]);
        $this->server->jsonUpdate('feature_data', 'security', [
            'password_authentication' => ['enabled' => false],
        ]);

        $after = $this->server->refresh()->securityScore();
        $this->assertGreaterThan($score['score'], $after['score']);
    }

    public function test_auto_update_runner_dispatches_only_due_servers(): void
    {
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
        Queue::assertPushed(UpdateJob::class, fn (UpdateJob $job) => $this->jobServerId($job) === $this->server->id);
        $this->assertNotEquals($notDue->id, $this->server->id);
    }

    private function jobServerId(UpdateJob $job): int
    {
        $reflection = new \ReflectionClass($job);
        $property = $reflection->getProperty('server');
        $property->setAccessible(true);

        /** @var Server $server */
        $server = $property->getValue($job);

        return $server->id;
    }
}
