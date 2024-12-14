<?php

namespace Database\Seeders;

use App\Models\CronJob;
use App\Models\Server;
use Illuminate\Database\Seeder;

class CronJobsSeeder extends Seeder
{
    public function run(): void
    {
        $servers = Server::all();

        foreach ($servers as $server) {
            CronJob::factory()->create([
                'server_id' => $server->id,
                'command' => 'php /home/vito/'.$server->project->name.'.com/artisan schedule:run',
            ]);
        }
    }
}
