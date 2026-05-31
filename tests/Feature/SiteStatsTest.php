<?php

namespace Tests\Feature;

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
use Tests\TestCase;

class SiteStatsTest extends TestCase
{
    use RefreshDatabase;

    private function installGoAccess(): Service
    {
        return Service::factory()->create([
            'server_id' => $this->server->id,
            'type' => 'log_analysis',
            'name' => 'goaccess',
            'version' => 'latest',
            'status' => ServiceStatus::READY,
            'is_default' => true,
            'type_data' => ['data_retention' => 12],
        ]);
    }

    private function sampleReport(): string
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

    public function test_stats_page_renders_without_service(): void
    {
        $this->actingAs($this->user);

        $this->get(route('site-stats', ['server' => $this->server, 'site' => $this->site]))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('sites/stats')->where('hasStatsService', false));
    }

    public function test_stats_page_reports_service_installed(): void
    {
        $this->actingAs($this->user);
        $this->installGoAccess();

        $this->get(route('site-stats', ['server' => $this->server, 'site' => $this->site]))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('hasStatsService', true));
    }

    public function test_conf_renderer_emits_expected_vars(): void
    {
        $conf = app(RenderSiteStatsConf::class)->render($this->site);

        $this->assertStringContainsString("SITE_ID='{$this->site->id}'", $conf);
        $this->assertStringContainsString("DOMAIN='{$this->site->domain}'", $conf);
        $this->assertStringContainsString("LOG_FORMAT='COMBINED'", $conf);
        $this->assertStringContainsString('RETENTION_MONTHS=', $conf);
        $this->assertStringContainsString("SSH_USER='{$this->server->getSshUser()}'", $conf);
    }

    public function test_conf_renderer_rejects_unsafe_domain(): void
    {
        $this->site->domain = 'bad domain; rm -rf /';
        $this->site->save();

        $this->expectException(\Exception::class);
        app(RenderSiteStatsConf::class)->render($this->site->refresh());
    }

    public function test_get_site_stats_parses_report_over_ssh(): void
    {
        SSH::fake($this->sampleReport());

        $stats = app(GetSiteStats::class)->get($this->site, '2026-05');

        $this->assertSame(200, $stats['detail']['totals']['visitors']);
        $this->assertSame(1000, $stats['detail']['totals']['hits']);
        $this->assertSame('/home', $stats['detail']['top_pages'][0]['name']);
        $this->assertSame('200', $stats['detail']['status_codes'][0]['name']);
        $this->assertSame(900, $stats['detail']['status_codes'][0]['hits']);
        $this->assertSame('/missing', $stats['detail']['not_found'][0]['name']);
        $this->assertSame(7, $stats['detail']['not_found'][0]['hits']);
    }

    public function test_json_endpoint_returns_detail(): void
    {
        SSH::fake($this->sampleReport());
        $this->actingAs($this->user);
        $this->installGoAccess();

        $this->get(route('site-stats.json', ['server' => $this->server, 'site' => $this->site, 'month' => '2026-05']))
            ->assertSuccessful()
            ->assertJsonPath('detail.totals.visitors', 200)
            ->assertJsonPath('detail.top_pages.0.name', '/home');
    }

    public function test_get_site_stats_tolerates_invalid_utf8_in_report(): void
    {
        Cache::flush();
        $report = str_replace('/home', "/home\x80\x81", $this->sampleReport());
        $this->assertFalse(mb_check_encoding($report, 'UTF-8'));
        SSH::fake($report);

        $stats = app(GetSiteStats::class)->get($this->site, '2026-05');

        $this->assertNotNull($stats['detail']);
        $this->assertSame(1000, $stats['detail']['totals']['hits']);
        $this->assertStringStartsWith('/home', $stats['detail']['top_pages'][0]['name']);
    }

    public function test_refresh_requires_service(): void
    {
        $this->actingAs($this->user);

        $this->post(route('site-stats.refresh', ['server' => $this->server, 'site' => $this->site]))
            ->assertNotFound();
    }

    public function test_refresh_dispatches_job_when_installed(): void
    {
        Queue::fake();
        $this->actingAs($this->user);
        $this->installGoAccess();

        $this->post(route('site-stats.refresh', ['server' => $this->server, 'site' => $this->site]))
            ->assertSessionDoesntHaveErrors();

        Queue::assertPushed(RefreshSiteStatsJob::class);
    }

    public function test_resync_requires_service_and_dispatches_job(): void
    {
        $this->actingAs($this->user);

        $this->post(route('log-analysis.resync', ['server' => $this->server]))->assertNotFound();

        Queue::fake();
        $this->installGoAccess();

        $this->post(route('log-analysis.resync', ['server' => $this->server]))->assertSessionDoesntHaveErrors();
        Queue::assertPushed(ResyncGoAccessJob::class);
    }

    public function test_site_created_listener_writes_conf_when_installed(): void
    {
        Queue::fake();
        $this->installGoAccess();

        (new HandleSiteCreatedStats)->handle(new SiteCreatedEvent($this->site));

        Queue::assertPushed(WriteSiteStatsConfJob::class);
    }

    public function test_site_created_listener_noop_without_service(): void
    {
        Queue::fake();

        (new HandleSiteCreatedStats)->handle(new SiteCreatedEvent($this->site));

        Queue::assertNotPushed(WriteSiteStatsConfJob::class);
    }

    public function test_site_deleted_listener_dispatches_cleanup_when_installed(): void
    {
        Queue::fake();
        $this->installGoAccess();

        (new HandleSiteDeletedStats)->handle(new SiteDeletedEvent($this->server, $this->site->id, $this->site->domain));

        Queue::assertPushed(CleanupSiteStatsJob::class);
    }

    public function test_sync_creates_named_root_cron(): void
    {
        SSH::fake('');
        $this->installGoAccess();

        app(SyncGoAccessServer::class)->sync($this->server);

        $this->assertDatabaseHas('cron_jobs', [
            'server_id' => $this->server->id,
            'user' => 'root',
            'hidden' => false,
            'name' => GoAccess::CRON_NAME,
            'command' => GoAccess::CRON_COMMAND,
            'status' => CronjobStatus::READY->value,
        ]);
    }

    public function test_resync_preserves_disabled_cron(): void
    {
        SSH::fake('');
        $this->installGoAccess();

        $cron = $this->server->cronJobs()->create([
            'site_id' => null,
            'name' => GoAccess::CRON_NAME,
            'user' => 'root',
            'command' => GoAccess::CRON_COMMAND,
            'frequency' => GoAccess::CRON_FREQUENCY,
            'status' => CronjobStatus::DISABLED,
        ]);

        app(SyncGoAccessServer::class)->sync($this->server);

        $this->assertSame(CronjobStatus::DISABLED, $cron->refresh()->status);
    }

    public function test_goaccess_can_be_managed(): void
    {
        $service = $this->installGoAccess();

        $this->assertTrue($service->handler()->canBeManaged());
    }

    public function test_stop_disables_cron_and_start_reenables(): void
    {
        SSH::fake('');
        $service = $this->installGoAccess();
        app(SyncGoAccessServer::class)->sync($this->server);

        $cron = $this->server->cronJobs()->where('command', GoAccess::CRON_COMMAND)->firstOrFail();

        $service->handler()->manage('stop');
        $this->assertSame(CronjobStatus::DISABLED, $cron->refresh()->status);

        $service->handler()->manage('start');
        $this->assertSame(CronjobStatus::READY, $cron->refresh()->status);
    }

    public function test_service_stop_route_works_for_goaccess(): void
    {
        Queue::fake();
        $this->actingAs($this->user);
        $service = $this->installGoAccess();

        $this->post(route('services.stop', ['server' => $this->server, 'service' => $service->id]))
            ->assertSessionDoesntHaveErrors();

        Queue::assertPushed(ManageJob::class);
    }

    public function test_stats_enabled_defaults_true(): void
    {
        $this->assertTrue($this->site->statsEnabled());
    }

    public function test_disable_sets_flag_and_dispatches_cleanup_when_installed(): void
    {
        Queue::fake();
        $this->installGoAccess();

        app(UpdateSiteStats::class)->disable($this->site);

        $this->assertFalse($this->site->refresh()->statsEnabled());
        $this->assertTrue($this->site->type_data['stats_disabled']);
        Queue::assertPushed(CleanupSiteStatsJob::class);
    }

    public function test_disable_without_service_does_not_dispatch(): void
    {
        Queue::fake();

        app(UpdateSiteStats::class)->disable($this->site);

        $this->assertFalse($this->site->refresh()->statsEnabled());
        Queue::assertNotPushed(CleanupSiteStatsJob::class);
    }

    public function test_enable_unsets_flag_and_dispatches_write_when_installed(): void
    {
        Queue::fake();
        $this->installGoAccess();
        $this->site->jsonUpdate('type_data', 'stats_disabled', true);

        app(UpdateSiteStats::class)->enable($this->site);

        $this->site->refresh();
        $this->assertTrue($this->site->statsEnabled());
        $this->assertArrayNotHasKey('stats_disabled', $this->site->type_data ?? []);
        Queue::assertPushed(WriteSiteStatsConfJob::class);
    }

    public function test_disable_route_disables_stats(): void
    {
        Queue::fake();
        $this->actingAs($this->user);
        $this->installGoAccess();

        $this->post(route('site-settings.disable-stats', ['server' => $this->server, 'site' => $this->site]))
            ->assertSessionDoesntHaveErrors();

        $this->assertFalse($this->site->refresh()->statsEnabled());
    }

    public function test_json_returns_404_when_stats_disabled(): void
    {
        SSH::fake($this->sampleReport());
        $this->actingAs($this->user);
        $this->installGoAccess();
        $this->site->jsonUpdate('type_data', 'stats_disabled', true);

        $this->get(route('site-stats.json', ['server' => $this->server, 'site' => $this->site, 'month' => '2026-05']))
            ->assertNotFound();
    }

    public function test_write_conf_job_noop_when_disabled(): void
    {
        SSH::fake('');
        $this->site->jsonUpdate('type_data', 'stats_disabled', true);

        (new WriteSiteStatsConfJob($this->site->refresh()))->handle();

        SSH::assertNotExecutedContains('sites/'.$this->site->id.'.conf');
        $this->assertFalse($this->site->statsEnabled());
    }

    public function test_stats_cron_is_subject_to_crontab_sync(): void
    {
        SSH::fake(''); // getUserCrontab returns empty for every user

        $cron = $this->server->cronJobs()->create([
            'site_id' => null,
            'name' => GoAccess::CRON_NAME,
            'user' => 'root',
            'command' => GoAccess::CRON_COMMAND,
            'frequency' => GoAccess::CRON_FREQUENCY,
            'status' => CronjobStatus::READY,
        ]);

        app(SyncCronJobs::class)->sync($this->server);

        $this->assertSame(CronjobStatus::DISABLED, $cron->refresh()->status);
    }
}
