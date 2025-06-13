<?php

namespace Tests\Traits;

use App\Enums\LoadBalancerMethod;
use App\Enums\ServiceStatus;
use App\Models\Server;
use App\Models\Site;
use App\SiteTypes\LoadBalancer;
use App\SiteTypes\PHPBlank;

trait PrepareLoadBalancer
{
    private function prepare(): void
    {
        $this->site->type = LoadBalancer::id();
        $this->site->type_data = [
            'method' => LoadBalancerMethod::ROUND_ROBIN,
        ];
        $this->site->save();

        $servers = Server::factory(2)->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);
        foreach ($servers as $server) {
            $server->services()->update([
                'status' => ServiceStatus::READY,
            ]);
            Site::factory()->create([
                'domain' => 'vito.test',
                'aliases' => ['www.vito.test'],
                'server_id' => $server->id,
                'type' => PHPBlank::id(),
                'path' => '/home/vito/vito.test',
                'web_directory' => '',
            ]);
        }
    }
}
