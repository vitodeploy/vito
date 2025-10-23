<?php

namespace App\Jobs\Server;

use App\Facades\Notifier;
use App\Models\Server;
use App\Notifications\ServerUpdateFailed;
use App\Traits\UniqueQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UpdateJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(protected Server $server) {}

    public function handle(): void
    {
        $this->run("server-{$this->server->id}", function () {
            $this->server->os()->upgrade();
            $this->server->checkConnection();
            $this->server->checkForUpdates();
        });
    }

    public function failed(): void
    {
        Notifier::send($this->server, new ServerUpdateFailed($this->server));
        $this->server->checkConnection();
    }
}
