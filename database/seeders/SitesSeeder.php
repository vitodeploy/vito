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
use App\SiteTypes\Wordpress;
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
                'aliases' => ['www.'.$server->project->name.'.com'],
            ]);
            $app->tags()->attach($server->tags()->first());
            Worker::factory()->create([
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

            /** @var Site $blog */
            $blog = Site::factory()->create([
                'server_id' => $server->id,
                'domain' => 'blog.'.$server->project->name.'.com',
                'type' => Wordpress::id(),
                'path' => '/home/vito/blog.'.$server->project->name.'.com',
                'aliases' => ['www.blog.'.$server->project->name.'.com'],
            ]);
            $blog->tags()->attach($server->tags()->first());
            Ssl::factory()->create([
                'site_id' => $blog->id,
                'type' => SslType::LETSENCRYPT,
                'expires_at' => now()->addYear(),
                'status' => SslStatus::CREATED,
            ]);
        }
    }
}
