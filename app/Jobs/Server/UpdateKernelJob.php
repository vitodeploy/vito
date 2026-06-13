<?php

namespace App\Jobs\Server;

use App\Actions\Server\BroadcastServerUpdate;
use App\Actions\Server\RebootServer;
use App\Facades\Notifier;
use App\Models\Server;
use App\Models\ServerLog;
use App\Notifications\ServerUpdateFailed;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UpdateKernelJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(protected Server $server) {}

    public function handle(): void
    {
        $this->run("server-{$this->server->id}", function () {
            $this->server->os()->upgradeKernel();
            $this->server->checkConnection();
            $this->server->checkForUpdates();
            app(BroadcastServerUpdate::class)->broadcast($this->server);
            app(RebootServer::class)->reboot($this->server);
        });
    }

    public function failed(Exception $e): void
    {
        Notifier::send($this->server, new ServerUpdateFailed($this->server));
        $this->server->checkConnection();
        app(BroadcastServerUpdate::class)->broadcast($this->server);

        ServerLog::log(
            $this->server,
            'update-kernel-failed',
            $e->getMessage()
        );
    }
}
