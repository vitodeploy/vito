<?php

namespace Database\Seeders;

use App\Models\Metric;
use App\Models\Server;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;

class MetricsSeeder extends Seeder
{
    public function run(): void
    {
        $servers = Server::query()->whereHas('services', function (Builder $query) {
            $query->where('type', 'monitoring');
        })->get();

        /** @var Server $server */
        foreach ($servers as $server) {
            $monitoring = $server->services()
                ->where('type', 'monitoring')
                ->first();
            $range = CarbonPeriod::create(Carbon::now()->subHour(), '1 minute', Carbon::now());
            foreach ($range as $date) {
                Metric::factory()->create([
                    'server_id' => $monitoring->server_id,
                    'created_at' => $date,
                ]);
            }
        }
    }
}
