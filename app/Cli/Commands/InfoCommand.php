<?php

namespace App\Cli\Commands;

use Illuminate\Console\Command;

class InfoCommand extends Command
{
    protected $signature = 'info';

    protected $description = 'Show the application information';

    public function handle(): void
    {
        $this->info('Version: '.config('app.version'));
        $this->info('Timezone: '.config('app.timezone'));
    }
}
