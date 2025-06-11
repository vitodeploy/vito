<?php

namespace App\Console\Commands;

use App\Enums\ServerStatus;
use App\Models\Server;
use Illuminate\Console\Command;

class CheckServersConnectionCommand extends Command
{
    protected $signature = 'servers:check';

    protected $description = 'Check servers connection status';

    public function handle(): void
    {
        Server::query()->whereIn('status', [
            ServerStatus::READY,
            ServerStatus::DISCONNECTED,
        ])->chunk(50, function ($servers) {
            $dispatchTime = now();
            /** @var Server $server */
            foreach ($servers as $server) {
                dispatch(function () use ($server, $dispatchTime) {
                    // check connection if dispatch time is less than 5 minutes ago
                    if ($dispatchTime->diffInMinutes(now()) > 5) {
                        return;
                    }
                    $server->checkConnection();
                })->onConnection('ssh');
            }
        });
    }
}
