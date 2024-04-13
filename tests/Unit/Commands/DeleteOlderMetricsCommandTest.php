<?php

namespace Tests\Unit\Commands;

use App\Enums\ServiceStatus;
use App\Models\Metric;
use App\Models\Service;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteOlderMetricsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_delete_older_metrics(): void
    {
        $monitoring = Service::factory()->create([
            'server_id' => $this->server->id,
            'name' => 'vito-agent',
            'type' => 'monitoring',
            'type_data' => [
                'data_retention' => 1,
            ],
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);

        $range = CarbonPeriod::create(Carbon::now()->subDays(10), '1 hour', Carbon::now()->subDays(8));
        foreach ($range as $date) {
            Metric::factory()->create([
                'server_id' => $monitoring->server_id,
                'created_at' => $date,
            ]);
        }

        $this->assertDatabaseHas('metrics', [
            'server_id' => $this->server->id,
        ]);

        $this->artisan('metrics:delete-older-metrics')
            ->expectsOutput('Metrics deleted');

        $this->assertDatabaseMissing('metrics', [
            'server_id' => $this->server->id,
        ]);
    }
}
