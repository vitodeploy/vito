<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PurgeBackupsCommand extends Command
{
    protected $signature = 'backups:purge';

    protected $description = 'Purges downloaded backup files that are 24 hours or older';

    public function handle(): void
    {
        $disk = Storage::disk('backups');
        $files = $disk->files();
        $purgedCount = 0;

        foreach ($files as $file) {
            $lastModified = $disk->lastModified($file);
            $daysSinceModified = (time() - $lastModified) / (60 * 60 * 24);

            if ($daysSinceModified > 1) {
                $disk->delete($file);
                $purgedCount++;
            }
        }

        if ($purgedCount > 0) {
            $this->info("Successfully purged {$purgedCount} old backup file(s).");
        } else {
            $this->info('No old backup files found to purge.');
        }
    }
}
