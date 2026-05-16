<?php

namespace App\Actions\Site;

use App\Enums\SiteStatus;
use App\Jobs\Site\CreateJob;
use App\Models\Site;
use Illuminate\Validation\ValidationException;

class RetrySite
{
    public function retry(Site $site): Site
    {
        if (! $site->isInstallationFailed()) {
            throw ValidationException::withMessages([
                'status' => 'Only sites in the installation_failed state can be retried.',
            ]);
        }

        $site->status = SiteStatus::INSTALLING;
        $site->last_error = null;
        $site->progress_step = null;
        $site->progress = 0;
        $site->save();

        dispatch(new CreateJob($site))->onQueue('ssh');

        return $site;
    }
}
