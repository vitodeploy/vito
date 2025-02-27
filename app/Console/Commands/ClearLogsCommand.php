<?php

namespace App\Console\Commands;

use App\Models\ServerLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ClearLogsCommand extends Command
{
    protected $signature = 'logs:clear';

    protected $description = 'Clear all server logs';

    public function handle(): void
    {
        $this->info('Clearing logs...');

        ServerLog::query()->delete();

        File::cleanDirectory(Storage::disk('server-logs')->path(''));

        $this->info('Logs cleared!');
    }
}
