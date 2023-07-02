<?php

namespace App\Jobs\Site;

use App\Jobs\Job;
use App\Models\Site;
use App\Models\SourceControl;
use Throwable;

class UpdateSourceControlsRemote extends Job
{
    protected SourceControl $sourceControl;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(SourceControl $sourceControl)
    {
        $this->sourceControl = $sourceControl;
    }

    /**
     * Execute the job.
     *
     * @throws Throwable
     */
    public function handle(): void
    {
        $sites = Site::query()
            ->where('user_id', $this->sourceControl->user_id)
            ->where('source_control', $this->sourceControl->provider)
            ->get();
        foreach ($sites as $site) {
            $site->server->ssh()->exec(
                'cd '.$site->path.' && git remote set-url origin '.$site->full_repository_url
            );
        }
    }
}
