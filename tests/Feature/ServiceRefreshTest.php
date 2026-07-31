<?php

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

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->server->services()->update(['status' => ServiceStatus::READY]);
});

test('refresh dispatches the job and flags the server', function () {
    Queue::fake();

    $this->actingAs($this->user);

    $this->post(route('services.refresh', ['server' => $this->server]))
        ->assertRedirect()
        ->assertSessionHas('info', 'Refreshing services…');

    Queue::assertPushed(RefreshServicesJob::class);
    expect(RefreshServices::refreshing($this->server))->toBeTrue();
});

test('refresh is a no op while already flagged', function () {
    Queue::fake();

    $this->actingAs($this->user);

    Cache::put("services-refreshing:{$this->server->id}", true, 900);

    $this->post(route('services.refresh', ['server' => $this->server]))
        ->assertRedirect()
        ->assertSessionHas('info', 'A services refresh is already running.');

    Queue::assertNothingPushed();
});

test('user role cannot refresh services', function () {
    $this->server->project->users()->where('user_id', $this->user->id)->update([
        'role' => UserRole::USER,
    ]);

    $this->actingAs($this->user);

    $this->post(route('services.refresh', ['server' => $this->server]))->assertForbidden();
});

test('refresh persists status version and networking in one ssh call', function () {
    $mysql = vitoPestFeatureServiceRefreshTestService('mysql');
    $redis = vitoPestFeatureServiceRefreshTestService('redis');

    SSH::fake(vitoPestFeatureServiceRefreshTestMarkers([
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

    expect($mysql->installed_version)->toEqual('8.4.5');
    expect($mysql->type_data['networking_effective'])->toBeTrue();
    expect($mysql->type_data['networking_checked_at'])->not->toBeNull();
    expect($mysql->status)->toEqual(ServiceStatus::READY);

    expect($redis->installed_version)->toEqual('7.2.4');
    expect($redis->type_data['networking_effective'])->toBeFalse();
    expect($redis->status)->toEqual(ServiceStatus::STOPPED);

    $script = vitoPestFeatureServiceRefreshTestExecutedScript();

    expect(substr_count($script, 'systemctl is-active'))->toEqual(1);
    $this->assertStringContainsString('###VITO:', $script);
});

test('refresh reads memory database config while stopped', function () {
    $redis = vitoPestFeatureServiceRefreshTestService('redis');

    SSH::fake(vitoPestFeatureServiceRefreshTestMarkers([
        [$redis->id, 'status', 'inactive'],
        [$redis->id, 'networking', 'bind 0.0.0.0'],
    ]));

    app(ProbeServices::class)->probe($this->server);

    expect($redis->refresh()->type_data['networking_effective'])->toBeTrue();
});

test('refresh survives a fragment with no trailing newline', function () {
    $php = vitoPestFeatureServiceRefreshTestService('php');
    $mysql = vitoPestFeatureServiceRefreshTestService('mysql');

    $output = "###VITO:{$php->id}:version###\n8.2.19"
        ."###VITO:{$mysql->id}:version###\n8.4.5\n";

    SSH::fake($output);

    app(ProbeServices::class)->probe($this->server);

    expect($mysql->refresh()->installed_version)->toBeNull('A glued marker must not be attributed to the following service.');

    SSH::fake(vitoPestFeatureServiceRefreshTestMarkers([
        [$php->id, 'version', '8.2.19'],
        [$mysql->id, 'version', '8.4.5'],
    ]));

    app(ProbeServices::class)->probe($this->server);

    expect($php->refresh()->installed_version)->toEqual('8.2.19');
    expect($mysql->refresh()->installed_version)->toEqual('8.4.5');

    $this->assertStringContainsString('printf \'%s\n\' "$OUT"', vitoPestFeatureServiceRefreshTestExecutedScript());
});

test('refresh script render is not html escaped', function () {
    $php = vitoPestFeatureServiceRefreshTestService('php');
    $php->version = "8.3'; rm -rf /tmp; echo '";
    $php->save();

    SSH::fake();

    app(ProbeServices::class)->probe($this->server);

    $script = vitoPestFeatureServiceRefreshTestExecutedScript();

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
});

test('refresh marks networking unknown when a running probe returns nothing', function () {
    $mysql = vitoPestFeatureServiceRefreshTestService('mysql');

    SSH::fake(vitoPestFeatureServiceRefreshTestMarkers([
        [$mysql->id, 'status', 'active'],
        [$mysql->id, 'networking', ''],
    ]));

    app(ProbeServices::class)->probe($this->server);

    $mysql->refresh();

    expect($mysql->type_data['networking_effective'])->toBeNull();
    expect($mysql->type_data['networking_checked_at'])->not->toBeNull('A probe that ran and could not read is still an observation.');
});

test('refresh leaves networking untouched for a stopped sql engine', function () {
    $mysql = vitoPestFeatureServiceRefreshTestService('mysql');
    $mysql->type_data = [
        'networking' => false,
        'networking_effective' => true,
        'networking_checked_at' => '2026-07-01T00:00:00+00:00',
    ];
    $mysql->save();

    SSH::fake(vitoPestFeatureServiceRefreshTestMarkers([[$mysql->id, 'status', 'inactive']]));

    app(ProbeServices::class)->probe($this->server);

    $mysql->refresh();

    expect($mysql->status)->toEqual(ServiceStatus::STOPPED);
    expect($mysql->type_data['networking_effective'])->toBeTrue('A skipped probe must not erase the last observation.');
    expect($mysql->type_data['networking_checked_at'])->toEqual('2026-07-01T00:00:00+00:00');

    $script = vitoPestFeatureServiceRefreshTestExecutedScript();

    $this->assertStringContainsString("if [ \"\$STATE_{$mysql->id}\" = \"active\" ]; then", $script);
});

test('refresh omits status section for unitless services', function () {
    $nodejs = vitoPestFeatureServiceRefreshTestService('nodejs');

    SSH::fake(vitoPestFeatureServiceRefreshTestMarkers([[$nodejs->id, 'version', '22.1.0']]));

    app(ProbeServices::class)->probe($this->server);

    $script = vitoPestFeatureServiceRefreshTestExecutedScript();

    $this->assertStringNotContainsString("###VITO:{$nodejs->id}:status###", $script);
    $this->assertStringContainsString("###VITO:{$nodejs->id}:version###", $script);
    expect($nodejs->refresh()->installed_version)->toEqual('22.1.0');
});

test('refresh skips version for handlers without a version command', function () {
    $agent = $this->server->services()->create([
        'type' => 'monitoring',
        'name' => 'vito-agent',
        'version' => '0.1.0',
        'status' => ServiceStatus::READY,
    ]);

    SSH::fake(vitoPestFeatureServiceRefreshTestMarkers([[$agent->id, 'status', 'active']]));

    app(ProbeServices::class)->probe($this->server);

    $this->assertStringNotContainsString("###VITO:{$agent->id}:version###", vitoPestFeatureServiceRefreshTestExecutedScript());
    expect($agent->refresh()->installed_version)->toBeNull();
});

test('refresh skips a service deleted mid batch and persists the rest', function () {
    $mysql = vitoPestFeatureServiceRefreshTestService('mysql');
    $redis = vitoPestFeatureServiceRefreshTestService('redis');
    $nginx = vitoPestFeatureServiceRefreshTestService('nginx');

    $output = vitoPestFeatureServiceRefreshTestMarkers([
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

    expect($mysql->refresh()->installed_version)->toEqual('8.4.5');
    expect($nginx->refresh()->installed_version)->toEqual('1.24.0', 'A service deleted mid-batch must not discard the services probed after it.');
});

test('refresh ignores a service whose sections are missing', function () {
    $mysql = vitoPestFeatureServiceRefreshTestService('mysql');
    $mysql->installed_version = '8.4.0';
    $mysql->save();

    SSH::fake('');

    app(ProbeServices::class)->probe($this->server);

    expect($mysql->refresh()->installed_version)->toEqual('8.4.0');
});

test('refresh parses sections out of order and keeps the first duplicate', function () {
    $mysql = vitoPestFeatureServiceRefreshTestService('mysql');

    SSH::fake(vitoPestFeatureServiceRefreshTestMarkers([
        [$mysql->id, 'version', '8.4.5'],
        [$mysql->id, 'status', 'active'],
        [$mysql->id, 'version', '9.9.9'],
        [99999, 'version', '1.2.3'],
    ]));

    app(ProbeServices::class)->probe($this->server);

    expect($mysql->refresh()->installed_version)->toEqual('8.4.5');
});

test('refresh parses crlf output and trims section values', function () {
    $mysql = vitoPestFeatureServiceRefreshTestService('mysql');

    SSH::fake("###VITO:{$mysql->id}:version###\r\n  8.4.5  \r\n");

    app(ProbeServices::class)->probe($this->server);

    expect($mysql->refresh()->installed_version)->toEqual('8.4.5');
});

test('refresh never creates the networking intent key', function () {
    $mysql = vitoPestFeatureServiceRefreshTestService('mysql');

    SSH::fake(vitoPestFeatureServiceRefreshTestMarkers([
        [$mysql->id, 'status', 'active'],
        [$mysql->id, 'networking', '0.0.0.0'],
    ]));

    app(ProbeServices::class)->probe($this->server);

    $mysql->refresh();

    $this->assertArrayNotHasKey('networking', $mysql->type_data);
    expect($mysql->handler()->networkingManaged())->toBeFalse();
});

test('refresh forces the status sync without double confirmation', function () {
    $mysql = vitoPestFeatureServiceRefreshTestService('mysql');

    SSH::fake(vitoPestFeatureServiceRefreshTestMarkers([[$mysql->id, 'status', 'failed']]));

    app(ProbeServices::class)->probe($this->server);

    expect($mysql->refresh()->status)->toEqual(ServiceStatus::FAILED);
});

test('refresh keeps the flag when the server lock is contended', function () {
    Cache::put("services-refreshing:{$this->server->id}", true, 900);

    $lock = Cache::lock("unique-queue:server-{$this->server->id}", 60);
    $lock->get();

    (new RefreshServicesJob($this->server))->handle();

    expect(RefreshServices::refreshing($this->server))->toBeTrue('A job released because the server lock was held must not clear the flag.');

    $lock->release();
});

test('refresh clears the flag and broadcasts on failure', function () {
    Cache::put("services-refreshing:{$this->server->id}", true, 900);

    (new RefreshServicesJob($this->server))->failed(new Exception('boom'));

    expect(RefreshServices::refreshing($this->server))->toBeFalse();
    $this->assertDatabaseHas('server_logs', [
        'server_id' => $this->server->id,
        'type' => 'refresh-services-failed',
    ]);
});

function vitoPestFeatureServiceRefreshTestService(string $name): Service
{
    /** @var Service $service */
    $service = test()->server->services()->where('name', $name)->firstOrFail();

    return $service;
}

/**
 * @param  array<int, array{0: int, 1: string, 2: string}>  $sections
 */
function vitoPestFeatureServiceRefreshTestMarkers(array $sections): string
{
    $output = '';

    foreach ($sections as [$id, $section, $body]) {
        $output .= "###VITO:{$id}:{$section}###\n{$body}\n";
    }

    return $output;
}

function vitoPestFeatureServiceRefreshTestExecutedScript(): string
{
    foreach (SSH::getExecutedCommands() as $command) {
        if (str_contains($command, '###VITO:')) {
            return $command;
        }
    }

    test()->fail('The refresh script was never executed.');
}
