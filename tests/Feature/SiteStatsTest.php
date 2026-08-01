<?php

use App\Actions\CronJob\SyncCronJobs;
use App\Actions\Site\UpdateSiteStats;
use App\Actions\SiteStats\GetSiteStats;
use App\Actions\SiteStats\RenderSiteStatsConf;
use App\Actions\SiteStats\SyncGoAccessServer;
use App\Enums\CronjobStatus;
use App\Enums\ServiceStatus;
use App\Events\SiteCreatedEvent;
use App\Events\SiteDeletedEvent;
use App\Facades\SSH;
use App\Jobs\Service\ManageJob;
use App\Jobs\Site\CleanupSiteStatsJob;
use App\Jobs\Site\RefreshSiteStatsJob;
use App\Jobs\Site\ResyncGoAccessJob;
use App\Jobs\Site\WriteSiteStatsConfJob;
use App\Listeners\HandleSiteCreatedStats;
use App\Listeners\HandleSiteDeletedStats;
use App\Models\Service;
use App\Services\LogAnalysis\GoAccess\GoAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

function vitoPestFeatureSiteStatsTestInstallGoAccess(): Service
{
    return Service::factory()->create([
        'server_id' => test()->server->id,
        'type' => 'log_analysis',
        'name' => 'goaccess',
        'version' => 'latest',
        'status' => ServiceStatus::READY,
        'is_default' => true,
        'type_data' => ['data_retention' => 12],
    ]);
}

function vitoPestFeatureSiteStatsTestSampleReport(): string
{
    return json_encode([
        'general' => ['total_requests' => 1000, 'unique_visitors' => 200, 'bandwidth' => 50000],
        'visitors' => ['data' => [
            ['data' => '20260501', 'hits' => ['count' => 100], 'visitors' => ['count' => 20], 'bytes' => ['count' => 5000]],
        ]],
        'requests' => ['data' => [
            ['data' => '/home', 'hits' => ['count' => 50], 'visitors' => ['count' => 10], 'bytes' => ['count' => 2500]],
        ]],
        'referrers' => ['data' => [
            ['data' => 'google.com', 'hits' => ['count' => 30], 'visitors' => ['count' => 8], 'bytes' => ['count' => 0]],
        ]],
        'status_codes' => ['data' => [
            ['data' => '2xx Success', 'items' => [['data' => '200', 'hits' => ['count' => 900]]]],
        ]],
        'not_found' => ['data' => [
            ['data' => '/missing', 'hits' => ['count' => 7], 'visitors' => ['count' => 5], 'bytes' => ['count' => 0]],
        ]],
    ]);
}

test('stats page renders without service', function () {
    $this->actingAs($this->user);

    $this->get(route('site-stats', ['server' => $this->server, 'site' => $this->site]))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('sites/stats')->where('hasStatsService', false));
});

test('stats page reports service installed', function () {
    $this->actingAs($this->user);
    vitoPestFeatureSiteStatsTestInstallGoAccess();

    $this->get(route('site-stats', ['server' => $this->server, 'site' => $this->site]))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('hasStatsService', true));
});

test('conf renderer emits expected vars', function () {
    $conf = app(RenderSiteStatsConf::class)->render($this->site);

    $this->assertStringContainsString("SITE_ID='{$this->site->id}'", $conf);
    $this->assertStringContainsString("DOMAIN='{$this->site->domain}'", $conf);
    $this->assertStringContainsString("LOG_FORMAT='COMBINED'", $conf);
    $this->assertStringContainsString('RETENTION_MONTHS=', $conf);
    $this->assertStringContainsString("SSH_USER='{$this->server->getSshUser()}'", $conf);
});

test('conf renderer rejects unsafe domain', function () {
    $this->site->domain = 'bad domain; rm -rf /';
    $this->site->save();

    $this->expectException(Exception::class);
    app(RenderSiteStatsConf::class)->render($this->site->refresh());
});

test('get site stats parses report over ssh', function () {
    SSH::fake(vitoPestFeatureSiteStatsTestSampleReport());

    $stats = app(GetSiteStats::class)->get($this->site, '2026-05');

    expect($stats['detail']['totals']['visitors'])->toBe(200);
    expect($stats['detail']['totals']['hits'])->toBe(1000);
    expect($stats['detail']['top_pages'][0]['name'])->toBe('/home');
    expect($stats['detail']['status_codes'][0]['name'])->toBe('200');
    expect($stats['detail']['status_codes'][0]['hits'])->toBe(900);
    expect($stats['detail']['not_found'][0]['name'])->toBe('/missing');
    expect($stats['detail']['not_found'][0]['hits'])->toBe(7);
});

test('json endpoint returns detail', function () {
    SSH::fake(vitoPestFeatureSiteStatsTestSampleReport());
    $this->actingAs($this->user);
    vitoPestFeatureSiteStatsTestInstallGoAccess();

    $this->get(route('site-stats.json', ['server' => $this->server, 'site' => $this->site, 'month' => '2026-05']))
        ->assertSuccessful()
        ->assertJsonPath('detail.totals.visitors', 200)
        ->assertJsonPath('detail.top_pages.0.name', '/home');
});

test('get site stats tolerates invalid utf8 in report', function () {
    Cache::flush();
    $report = str_replace('/home', "/home\x80\x81", vitoPestFeatureSiteStatsTestSampleReport());
    expect(mb_check_encoding($report, 'UTF-8'))->toBeFalse();
    SSH::fake($report);

    $stats = app(GetSiteStats::class)->get($this->site, '2026-05');

    expect($stats['detail'])->not->toBeNull();
    expect($stats['detail']['totals']['hits'])->toBe(1000);
    expect($stats['detail']['top_pages'][0]['name'])->toStartWith('/home');
});

test('refresh requires service', function () {
    $this->actingAs($this->user);

    $this->post(route('site-stats.refresh', ['server' => $this->server, 'site' => $this->site]))
        ->assertNotFound();
});

test('refresh dispatches job when installed', function () {
    Queue::fake();
    $this->actingAs($this->user);
    vitoPestFeatureSiteStatsTestInstallGoAccess();

    $this->post(route('site-stats.refresh', ['server' => $this->server, 'site' => $this->site]))
        ->assertSessionDoesntHaveErrors();

    Queue::assertPushed(RefreshSiteStatsJob::class);
});

test('resync requires service and dispatches job', function () {
    $this->actingAs($this->user);

    $this->post(route('log-analysis.resync', ['server' => $this->server]))->assertNotFound();

    Queue::fake();
    vitoPestFeatureSiteStatsTestInstallGoAccess();

    $this->post(route('log-analysis.resync', ['server' => $this->server]))->assertSessionDoesntHaveErrors();
    Queue::assertPushed(ResyncGoAccessJob::class);
});

test('site created listener writes conf when installed', function () {
    Queue::fake();
    vitoPestFeatureSiteStatsTestInstallGoAccess();

    (new HandleSiteCreatedStats)->handle(new SiteCreatedEvent($this->site));

    Queue::assertPushed(WriteSiteStatsConfJob::class);
});

test('site created listener noop without service', function () {
    Queue::fake();

    (new HandleSiteCreatedStats)->handle(new SiteCreatedEvent($this->site));

    Queue::assertNotPushed(WriteSiteStatsConfJob::class);
});

test('site deleted listener dispatches cleanup when installed', function () {
    Queue::fake();
    vitoPestFeatureSiteStatsTestInstallGoAccess();

    (new HandleSiteDeletedStats)->handle(new SiteDeletedEvent($this->server, $this->site->id, $this->site->domain));

    Queue::assertPushed(CleanupSiteStatsJob::class);
});

test('sync creates named root cron', function () {
    SSH::fake('');
    vitoPestFeatureSiteStatsTestInstallGoAccess();

    app(SyncGoAccessServer::class)->sync($this->server);

    $this->assertDatabaseHas('cron_jobs', [
        'server_id' => $this->server->id,
        'user' => 'root',
        'hidden' => false,
        'name' => GoAccess::CRON_NAME,
        'command' => GoAccess::CRON_COMMAND,
        'status' => CronjobStatus::READY->value,
    ]);
});

test('resync preserves disabled cron', function () {
    SSH::fake('');
    vitoPestFeatureSiteStatsTestInstallGoAccess();

    $cron = $this->server->cronJobs()->create([
        'site_id' => null,
        'name' => GoAccess::CRON_NAME,
        'user' => 'root',
        'command' => GoAccess::CRON_COMMAND,
        'frequency' => GoAccess::CRON_FREQUENCY,
        'status' => CronjobStatus::DISABLED,
    ]);

    app(SyncGoAccessServer::class)->sync($this->server);

    expect($cron->refresh()->status)->toBe(CronjobStatus::DISABLED);
});

test('goaccess can be managed', function () {
    $service = vitoPestFeatureSiteStatsTestInstallGoAccess();

    expect($service->handler()->canBeManaged())->toBeTrue();
});

test('stop disables cron and start reenables', function () {
    SSH::fake('');
    $service = vitoPestFeatureSiteStatsTestInstallGoAccess();
    app(SyncGoAccessServer::class)->sync($this->server);

    $cron = $this->server->cronJobs()->where('command', GoAccess::CRON_COMMAND)->firstOrFail();

    $service->handler()->manage('stop');
    expect($cron->refresh()->status)->toBe(CronjobStatus::DISABLED);

    $service->handler()->manage('start');
    expect($cron->refresh()->status)->toBe(CronjobStatus::READY);
});

test('service stop route works for goaccess', function () {
    Queue::fake();
    $this->actingAs($this->user);
    $service = vitoPestFeatureSiteStatsTestInstallGoAccess();

    $this->post(route('services.stop', ['server' => $this->server, 'service' => $service->id]))
        ->assertSessionDoesntHaveErrors();

    Queue::assertPushed(ManageJob::class);
});

test('stats enabled defaults true', function () {
    expect($this->site->statsEnabled())->toBeTrue();
});

test('disable sets flag and dispatches cleanup when installed', function () {
    Queue::fake();
    vitoPestFeatureSiteStatsTestInstallGoAccess();

    app(UpdateSiteStats::class)->disable($this->site);

    expect($this->site->refresh()->statsEnabled())->toBeFalse();
    expect($this->site->type_data['stats_disabled'])->toBeTrue();
    Queue::assertPushed(CleanupSiteStatsJob::class);
});

test('disable without service does not dispatch', function () {
    Queue::fake();

    app(UpdateSiteStats::class)->disable($this->site);

    expect($this->site->refresh()->statsEnabled())->toBeFalse();
    Queue::assertNotPushed(CleanupSiteStatsJob::class);
});

test('enable unsets flag and dispatches write when installed', function () {
    Queue::fake();
    vitoPestFeatureSiteStatsTestInstallGoAccess();
    $this->site->jsonUpdate('type_data', 'stats_disabled', true);

    app(UpdateSiteStats::class)->enable($this->site);

    $this->site->refresh();
    expect($this->site->statsEnabled())->toBeTrue();
    $this->assertArrayNotHasKey('stats_disabled', $this->site->type_data ?? []);
    Queue::assertPushed(WriteSiteStatsConfJob::class);
});

test('disable route disables stats', function () {
    Queue::fake();
    $this->actingAs($this->user);
    vitoPestFeatureSiteStatsTestInstallGoAccess();

    $this->post(route('site-settings.disable-stats', ['server' => $this->server, 'site' => $this->site]))
        ->assertSessionDoesntHaveErrors();

    expect($this->site->refresh()->statsEnabled())->toBeFalse();
});

test('json returns 404 when stats disabled', function () {
    SSH::fake(vitoPestFeatureSiteStatsTestSampleReport());
    $this->actingAs($this->user);
    vitoPestFeatureSiteStatsTestInstallGoAccess();
    $this->site->jsonUpdate('type_data', 'stats_disabled', true);

    $this->get(route('site-stats.json', ['server' => $this->server, 'site' => $this->site, 'month' => '2026-05']))
        ->assertNotFound();
});

test('write conf job noop when disabled', function () {
    SSH::fake('');
    $this->site->jsonUpdate('type_data', 'stats_disabled', true);

    (new WriteSiteStatsConfJob($this->site->refresh()))->handle();

    SSH::assertNotExecutedContains('sites/'.$this->site->id.'.conf');
    expect($this->site->statsEnabled())->toBeFalse();
});

test('stats cron is subject to crontab sync', function () {
    SSH::fake('');

    // getUserCrontab returns empty for every user
    $cron = $this->server->cronJobs()->create([
        'site_id' => null,
        'name' => GoAccess::CRON_NAME,
        'user' => 'root',
        'command' => GoAccess::CRON_COMMAND,
        'frequency' => GoAccess::CRON_FREQUENCY,
        'status' => CronjobStatus::READY,
    ]);

    app(SyncCronJobs::class)->sync($this->server);

    expect($cron->refresh()->status)->toBe(CronjobStatus::DISABLED);
});
