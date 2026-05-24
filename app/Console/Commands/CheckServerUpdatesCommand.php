<?php

namespace App\Console\Commands;

use App\Enums\ServerStatus;
use App\Jobs\Server\CheckForUpdatesJob;
use App\Models\Server;
use Illuminate\Console\Command;

class CheckServerUpdatesCommand extends Command
{
    protected $signature = 'servers:check-updates';

    protected $description = 'Check each server for available package updates';

    public function handle(): void
    {
        Server::query()
            ->where('status', ServerStatus::READY)
            ->chunk(50, function ($servers) {
                /** @var Server $server */
                foreach ($servers as $server) {
                    CheckForUpdatesJob::dispatch($server)->onQueue('ssh');
                }
            });
    }
}
