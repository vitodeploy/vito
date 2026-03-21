<?php

namespace Database\Seeders;

use App\Enums\SslStatus;
use App\Enums\SslType;
use App\Enums\WorkerStatus;
use App\Models\Server;
use App\Models\Site;
use App\Models\SourceControl;
use App\Models\Ssl;
use App\Models\Worker;
use App\SiteTypes\Laravel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;

class SitesSeeder extends Seeder
{
    public function run(): void
    {
        $servers = Server::query()->whereHas('services', function (Builder $query): void {
            $query->where('type', 'webserver');
        })->get();

        $sourceControls = SourceControl::all();

        /** @var Server $server */
        foreach ($servers as $server) {
            /** @var Site $app */
            $app = Site::factory()->create([
                'server_id' => $server->id,
                'domain' => $server->project->name.'.com',
                'source_control_id' => $sourceControls->random()->id,
                'type' => Laravel::id(),
                'path' => '/home/vito/'.$server->project->name.'.com',
            ]);
            Worker::factory()->create([
                'server_id' => $server->id,
                'site_id' => $app->id,
                'command' => 'php artisan queue:work',
                'status' => WorkerStatus::RUNNING,
            ]);
            Ssl::factory()->create([
                'site_id' => $app->id,
                'type' => SslType::LETSENCRYPT,
                'expires_at' => now()->addYear(),
                'status' => SslStatus::CREATED,
            ]);
        }
    }
}
