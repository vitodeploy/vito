<?php

namespace App\Actions\Application;

use App\Enums\ApplicationStatus;
use App\Jobs\Application\DeleteJob;
use App\Models\Application;

class DeleteApplication
{
    public function delete(Application $application): void
    {
        $application->status = ApplicationStatus::DELETING;
        $application->save();

        dispatch(new DeleteJob($application))->onQueue('ssh');
    }
}
