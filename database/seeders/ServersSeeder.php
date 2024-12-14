<?php

namespace Database\Seeders;

use App\Actions\Server\CreateServer;
use App\Enums\ServiceStatus;
use App\Models\Project;
use App\Models\Server;
use App\Models\ServerProvider;
use App\Models\Service;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class ServersSeeder extends Seeder
{
    public function run(): void
    {
        /** @var User $user */
        $user = User::query()->first();

        $projects = Project::all();
        foreach ($projects as $project) {
            $this->createResources($user, $project);
        }
    }

    private function createResources(User $user, Project $project): void
    {
        $providers = ServerProvider::all();

        /** @var Tag $tag */
        foreach ($project->tags()->get() as $tag) {
            $provider = $providers->random();
            // database
            /** @var Server $db */
            $db = Server::factory()->create([
                'user_id' => $user->id,
                'project_id' => $project->id,
                'name' => $tag->name.'-'.'database',
                'provider' => $provider->provider,
                'provider_id' => $provider->id,
            ]);
            $db->tags()->attach($tag->id);
            $this->database($db);
            $this->firewall($db);
            $this->monitoring($db);
            $this->redis($db);

            // app-1
            /** @var Server $app */
            $app = Server::factory()->create([
                'user_id' => $user->id,
                'project_id' => $project->id,
                'name' => $tag->name.'-'.'app-1',
                'provider' => $provider->provider,
                'provider_id' => $provider->id,
            ]);
            $app->tags()->attach($tag->id);
            $this->webserver($app);
            $this->php($app);
            $this->firewall($app);
            $this->monitoring($app);
            $this->supervisor($app);
            $this->redis($app);
        }
    }

    private function database(Server $server): void
    {
        Service::query()->create([
            'server_id' => $server->id,
            'type' => 'database',
            'name' => config('core.databases_name.mysql80'),
            'version' => config('core.databases_version.mysql80'),
            'status' => ServiceStatus::READY,
        ]);
    }

    private function webserver(Server $server): void
    {
        Service::query()->create([
            'server_id' => $server->id,
            'type' => 'webserver',
            'name' => 'nginx',
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);
    }

    private function php(Server $server): void
    {
        Service::query()->create([
            'server_id' => $server->id,
            'type' => 'php',
            'name' => 'php',
            'version' => '8.2',
            'status' => ServiceStatus::READY,
        ]);
    }

    private function firewall(Server $server): void
    {
        Service::query()->create([
            'server_id' => $server->id,
            'type' => 'firewall',
            'name' => 'ufw',
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);
        app(CreateServer::class)->createFirewallRules($server);
    }

    private function monitoring(Server $server): void
    {
        Service::query()->create([
            'server_id' => $server->id,
            'type' => 'monitoring',
            'name' => 'remote-monitor',
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);
    }

    private function redis(Server $server): void
    {
        Service::query()->create([
            'server_id' => $server->id,
            'type' => 'memory_database',
            'name' => 'redis',
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);
    }

    private function supervisor(Server $server): void
    {
        Service::query()->create([
            'server_id' => $server->id,
            'type' => 'process_manager',
            'name' => 'supervisor',
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);
    }
}
