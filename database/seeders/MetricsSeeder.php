<?php

namespace Database\Seeders;

use App\Models\Metric;
use App\Models\Service;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;

class MetricsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Metric::query()->delete();

        $monitoring = Service::query()
            ->where('type', 'monitoring')
            ->firstOrFail();

        $range = CarbonPeriod::create(Carbon::now()->subDays(7), '1 minute', Carbon::now());
        foreach ($range as $date) {
            Metric::factory()->create([
                'server_id' => $monitoring->server_id,
                'created_at' => $date,
            ]);
        }
    }
}
