<?php

namespace Tests\Feature;

use App\Actions\Service\ProbeServices;
use App\Actions\Service\RefreshServices;
use App\Enums\ServiceStatus;
use App\Enums\UserRole;
use App\Facades\SSH;
use App\Jobs\Service\RefreshServicesJob;
use App\Models\Service;
use App\Support\Testing\SSHFake;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ServiceRefreshTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->server->services()->update(['status' => ServiceStatus::READY]);
    }

    public function test_refresh_dispatches_the_job_and_flags_the_server(): void
    {
        Queue::fake();

        $this->actingAs($this->user);

        $this->post(route('services.refresh', ['server' => $this->server]))
            ->assertRedirect()
            ->assertSessionHas('info', 'Refreshing services…');

        Queue::assertPushed(RefreshServicesJob::class);
        $this->assertTrue(RefreshServices::refreshing($this->server));
    }

    public function test_refresh_is_a_no_op_while_already_flagged(): void
    {
        Queue::fake();

        $this->actingAs($this->user);

        Cache::put("services-refreshing:{$this->server->id}", true, 900);

        $this->post(route('services.refresh', ['server' => $this->server]))
            ->assertRedirect()
            ->assertSessionHas('info', 'A services refresh is already running.');

        Queue::assertNothingPushed();
    }

    public function test_user_role_cannot_refresh_services(): void
    {
        $this->server->project->users()->where('user_id', $this->user->id)->update([
            'role' => UserRole::USER,
        ]);

        $this->actingAs($this->user);

        $this->post(route('services.refresh', ['server' => $this->server]))->assertForbidden();
    }

    public function test_refresh_persists_status_version_and_networking_in_one_ssh_call(): void
    {
        $mysql = $this->service('mysql');
        $redis = $this->service('redis');

        SSH::fake($this->markers([
            [$mysql->id, 'status', 'active'],
            [$mysql->id, 'version', '8.4.5'],
            [$mysql->id, 'networking', '0.0.0.0'],
            [$redis->id, 'status', 'inactive'],
            [$redis->id, 'version', '7.2.4'],
            [$redis->id, 'networking', 'bind 127.0.0.1 -::1'],
        ]));

        app(ProbeServices::class)->probe($this->server);

        $mysql->refresh();
        $redis->refresh();

        $this->assertEquals('8.4.5', $mysql->installed_version);
        $this->assertTrue($mysql->type_data['networking_effective']);
        $this->assertNotNull($mysql->type_data['networking_checked_at']);
        $this->assertEquals(ServiceStatus::READY, $mysql->status);

        $this->assertEquals('7.2.4', $redis->installed_version);
        $this->assertFalse($redis->type_data['networking_effective']);
        $this->assertEquals(ServiceStatus::STOPPED, $redis->status);

        $script = $this->executedScript();

        $this->assertEquals(1, substr_count($script, 'systemctl is-active'));
        $this->assertStringContainsString('###VITO:', $script);
    }

    public function test_refresh_reads_memory_database_config_while_stopped(): void
    {
        $redis = $this->service('redis');

        SSH::fake($this->markers([
            [$redis->id, 'status', 'inactive'],
            [$redis->id, 'networking', 'bind 0.0.0.0'],
        ]));

        app(ProbeServices::class)->probe($this->server);

        $this->assertTrue($redis->refresh()->type_data['networking_effective']);
    }

    public function test_refresh_survives_a_fragment_with_no_trailing_newline(): void
    {
        $php = $this->service('php');
        $mysql = $this->service('mysql');

        $output = "###VITO:{$php->id}:version###\n8.2.19"
            ."###VITO:{$mysql->id}:version###\n8.4.5\n";

        SSH::fake($output);

        app(ProbeServices::class)->probe($this->server);

        $this->assertNull(
            $mysql->refresh()->installed_version,
            'A glued marker must not be attributed to the following service.'
        );

        SSH::fake($this->markers([
            [$php->id, 'version', '8.2.19'],
            [$mysql->id, 'version', '8.4.5'],
        ]));

        app(ProbeServices::class)->probe($this->server);

        $this->assertEquals('8.2.19', $php->refresh()->installed_version);
        $this->assertEquals('8.4.5', $mysql->refresh()->installed_version);

        $this->assertStringContainsString('printf \'%s\n\' "$OUT"', $this->executedScript());
    }

    public function test_refresh_script_render_is_not_html_escaped(): void
    {
        $php = $this->service('php');
        $php->version = "8.3'; rm -rf /tmp; echo '";
        $php->save();

        SSH::fake();

        app(ProbeServices::class)->probe($this->server);

        $script = $this->executedScript();

        $this->assertStringContainsString('grep -oE', $script);
        $this->assertStringNotContainsString('&#039;', $script);
        $this->assertStringNotContainsString('&quot;', $script);
        $this->assertStringNotContainsString(
            "php8.3'; rm -rf /tmp",
            $script,
            'The hostile version must never reach the script with its quote unescaped.'
        );
        $this->assertStringContainsString(escapeshellarg((string) $php->handler()->versionCommand()), $script);
        $this->assertStringContainsString('timeout 15 bash -c', $script);
    }

    public function test_refresh_marks_networking_unknown_when_a_running_probe_returns_nothing(): void
    {
        $mysql = $this->service('mysql');

        SSH::fake($this->markers([
            [$mysql->id, 'status', 'active'],
            [$mysql->id, 'networking', ''],
        ]));

        app(ProbeServices::class)->probe($this->server);

        $mysql->refresh();

        $this->assertNull($mysql->type_data['networking_effective']);
        $this->assertNotNull(
            $mysql->type_data['networking_checked_at'],
            'A probe that ran and could not read is still an observation.'
        );
    }

    public function test_refresh_leaves_networking_untouched_for_a_stopped_sql_engine(): void
    {
        $mysql = $this->service('mysql');
        $mysql->type_data = [
            'networking' => false,
            'networking_effective' => true,
            'networking_checked_at' => '2026-07-01T00:00:00+00:00',
        ];
        $mysql->save();

        SSH::fake($this->markers([[$mysql->id, 'status', 'inactive']]));

        app(ProbeServices::class)->probe($this->server);

        $mysql->refresh();

        $this->assertEquals(ServiceStatus::STOPPED, $mysql->status);
        $this->assertTrue(
            $mysql->type_data['networking_effective'],
            'A skipped probe must not erase the last observation.'
        );
        $this->assertEquals('2026-07-01T00:00:00+00:00', $mysql->type_data['networking_checked_at']);

        $script = $this->executedScript();

        $this->assertStringContainsString("if [ \"\$STATE_{$mysql->id}\" = \"active\" ]; then", $script);
    }

    public function test_refresh_omits_status_section_for_unitless_services(): void
    {
        $nodejs = $this->service('nodejs');

        SSH::fake($this->markers([[$nodejs->id, 'version', '22.1.0']]));

        app(ProbeServices::class)->probe($this->server);

        $script = $this->executedScript();

        $this->assertStringNotContainsString("###VITO:{$nodejs->id}:status###", $script);
        $this->assertStringContainsString("###VITO:{$nodejs->id}:version###", $script);
        $this->assertEquals('22.1.0', $nodejs->refresh()->installed_version);
    }

    public function test_refresh_skips_version_for_handlers_without_a_version_command(): void
    {
        $agent = $this->server->services()->create([
            'type' => 'monitoring',
            'name' => 'vito-agent',
            'version' => '0.1.0',
            'status' => ServiceStatus::READY,
        ]);

        SSH::fake($this->markers([[$agent->id, 'status', 'active']]));

        app(ProbeServices::class)->probe($this->server);

        $this->assertStringNotContainsString("###VITO:{$agent->id}:version###", $this->executedScript());
        $this->assertNull($agent->refresh()->installed_version);
    }

    public function test_refresh_skips_a_service_deleted_mid_batch_and_persists_the_rest(): void
    {
        $mysql = $this->service('mysql');
        $redis = $this->service('redis');
        $nginx = $this->service('nginx');

        $output = $this->markers([
            [$mysql->id, 'version', '8.4.5'],
            [$redis->id, 'version', '7.2.4'],
            [$nginx->id, 'version', '1.24.0'],
        ]);

        SSH::swap(new class($output, $redis->id) extends SSHFake
        {
            public function __construct(private readonly string $markerOutput, private readonly int $deleteServiceId)
            {
                parent::__construct($markerOutput);
            }

            public function exec(string|View $command, string $log = '', ?int $siteId = null, ?bool $stream = false, ?callable $streamCallback = null, int $timeout = 0): string
            {
                Service::query()->whereKey($this->deleteServiceId)->delete();

                return $this->markerOutput;
            }
        });

        app(ProbeServices::class)->probe($this->server);

        $this->assertEquals('8.4.5', $mysql->refresh()->installed_version);
        $this->assertEquals(
            '1.24.0',
            $nginx->refresh()->installed_version,
            'A service deleted mid-batch must not discard the services probed after it.'
        );
    }

    public function test_refresh_ignores_a_service_whose_sections_are_missing(): void
    {
        $mysql = $this->service('mysql');
        $mysql->installed_version = '8.4.0';
        $mysql->save();

        SSH::fake('');

        app(ProbeServices::class)->probe($this->server);

        $this->assertEquals('8.4.0', $mysql->refresh()->installed_version);
    }

    public function test_refresh_parses_sections_out_of_order_and_keeps_the_first_duplicate(): void
    {
        $mysql = $this->service('mysql');

        SSH::fake($this->markers([
            [$mysql->id, 'version', '8.4.5'],
            [$mysql->id, 'status', 'active'],
            [$mysql->id, 'version', '9.9.9'],
            [99999, 'version', '1.2.3'],
        ]));

        app(ProbeServices::class)->probe($this->server);

        $this->assertEquals('8.4.5', $mysql->refresh()->installed_version);
    }

    public function test_refresh_parses_crlf_output_and_trims_section_values(): void
    {
        $mysql = $this->service('mysql');

        SSH::fake("###VITO:{$mysql->id}:version###\r\n  8.4.5  \r\n");

        app(ProbeServices::class)->probe($this->server);

        $this->assertEquals('8.4.5', $mysql->refresh()->installed_version);
    }

    public function test_refresh_never_creates_the_networking_intent_key(): void
    {
        $mysql = $this->service('mysql');

        SSH::fake($this->markers([
            [$mysql->id, 'status', 'active'],
            [$mysql->id, 'networking', '0.0.0.0'],
        ]));

        app(ProbeServices::class)->probe($this->server);

        $mysql->refresh();

        $this->assertArrayNotHasKey('networking', $mysql->type_data);
        $this->assertFalse($mysql->handler()->networkingManaged());
    }

    public function test_refresh_forces_the_status_sync_without_double_confirmation(): void
    {
        $mysql = $this->service('mysql');

        SSH::fake($this->markers([[$mysql->id, 'status', 'failed']]));

        app(ProbeServices::class)->probe($this->server);

        $this->assertEquals(ServiceStatus::FAILED, $mysql->refresh()->status);
    }

    public function test_refresh_keeps_the_flag_when_the_server_lock_is_contended(): void
    {
        Cache::put("services-refreshing:{$this->server->id}", true, 900);

        $lock = Cache::lock("unique-queue:server-{$this->server->id}", 60);
        $lock->get();

        (new RefreshServicesJob($this->server))->handle();

        $this->assertTrue(
            RefreshServices::refreshing($this->server),
            'A job released because the server lock was held must not clear the flag.'
        );

        $lock->release();
    }

    public function test_refresh_clears_the_flag_and_broadcasts_on_failure(): void
    {
        Cache::put("services-refreshing:{$this->server->id}", true, 900);

        (new RefreshServicesJob($this->server))->failed(new \Exception('boom'));

        $this->assertFalse(RefreshServices::refreshing($this->server));
        $this->assertDatabaseHas('server_logs', [
            'server_id' => $this->server->id,
            'type' => 'refresh-services-failed',
        ]);
    }

    private function service(string $name): Service
    {
        /** @var Service $service */
        $service = $this->server->services()->where('name', $name)->firstOrFail();

        return $service;
    }

    /**
     * @param  array<int, array{0: int, 1: string, 2: string}>  $sections
     */
    private function markers(array $sections): string
    {
        $output = '';

        foreach ($sections as [$id, $section, $body]) {
            $output .= "###VITO:{$id}:{$section}###\n{$body}\n";
        }

        return $output;
    }

    private function executedScript(): string
    {
        foreach (SSH::getExecutedCommands() as $command) {
            if (str_contains($command, '###VITO:')) {
                return $command;
            }
        }

        $this->fail('The refresh script was never executed.');
    }
}
