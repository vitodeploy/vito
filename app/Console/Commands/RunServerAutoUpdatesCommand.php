<?php

namespace App\Console\Commands;

use App\Actions\Server\Update;
use App\Enums\ServerStatus;
use App\Models\Server;
use Cron\CronExpression;
use Illuminate\Console\Command;

class RunServerAutoUpdatesCommand extends Command
{
    protected $signature = 'servers:auto-update';

    protected $description = 'Dispatch package updates to servers whose auto-update schedule is due';

    public function handle(): void
    {
        Server::query()
            ->where('status', ServerStatus::READY)
            ->where('auto_update', true)
            ->whereNotNull('auto_update_schedule')
            ->chunk(50, function ($servers): void {
                /** @var Server $server */
                foreach ($servers as $server) {
                    if (! CronExpression::isValidExpression((string) $server->auto_update_schedule)) {
                        continue;
                    }

                    if ((new CronExpression((string) $server->auto_update_schedule))->isDue(now(), config('app.timezone'))) {
                        app(Update::class)->update($server);
                    }
                }
            });
    }
}
