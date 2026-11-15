<?php

namespace App\Jobs\Component;

use App\Actions\Component\SyncLoadBalancerBackends;
use App\Models\Server;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncLoadBalancerServerJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(public int $serverId)
    {
        $this->onQueue('ssh');
    }

    public function handle(): void
    {
        $this->run('server-'.$this->serverId, function (): void {
            $server = Server::query()->find($this->serverId);
            if ($server) {
                app(SyncLoadBalancerBackends::class)->sync($server);
            }
        });
    }

    public function failed(Exception $e): void
    {
        app(SyncLoadBalancerBackends::class)->failed($this->serverId);
    }
}
