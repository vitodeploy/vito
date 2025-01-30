<?php

namespace Tests\Traits;

use App\Enums\Database;
use App\Enums\LoadBalancerMethod;
use App\Enums\PHP;
use App\Enums\ServiceStatus;
use App\Enums\SiteType;
use App\Enums\Webserver;
use App\Models\Server;
use App\Models\Site;

trait PrepareLoadBalancer
{
    private function prepare(): void
    {
        $this->site->type = SiteType::LOAD_BALANCER;
        $this->site->type_data = [
            'method' => LoadBalancerMethod::ROUND_ROBIN,
        ];
        $this->site->save();

        $servers = Server::factory(2)->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);
        foreach ($servers as $server) {
            $server->type()->createServices([
                'webserver' => Webserver::NGINX,
                'database' => Database::NONE,
                'php' => PHP::NONE,
            ]);
            $server->services()->update([
                'status' => ServiceStatus::READY,
            ]);
            Site::factory()->create([
                'domain' => 'vito.test',
                'aliases' => ['www.vito.test'],
                'server_id' => $server->id,
                'type' => SiteType::PHP_BLANK,
                'path' => '/home/vito/vito.test',
                'web_directory' => '',
            ]);
        }
    }
}
