<?php

namespace App\Console\Commands;

use App\Actions\GithubApp\SyncGithubAppInstallations;
use App\Models\GithubApp;
use Illuminate\Console\Command;

class SyncGithubAppInstallationsCommand extends Command
{
    protected $signature = 'github-app:sync';

    protected $description = 'Reconcile local GitHub App SourceControls with installations on GitHub.';

    public function handle(SyncGithubAppInstallations $action): int
    {
        if (! GithubApp::query()->exists()) {
            $this->info('GitHub App not configured. Nothing to sync.');

            return self::SUCCESS;
        }

        $result = $action->run();

        $this->info(sprintf(
            'Sync complete. Added: %d, removed: %d, kept: %d.',
            $result['added'],
            $result['removed'],
            $result['kept']
        ));

        return self::SUCCESS;
    }
}
