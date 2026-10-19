<?php

namespace App\Console\Commands;

use App\Actions\GitHook\SyncRepositoryGitHook;
use Illuminate\Console\Command;

class SyncRepositoryGitHooksCommand extends Command
{
    protected $signature = 'git-hooks:sync-repositories';

    protected $description = 'Reconcile shared repository hooks for automatic environment deployments';

    public function handle(): void
    {
        app(SyncRepositoryGitHook::class)->reconcile();
    }
}
