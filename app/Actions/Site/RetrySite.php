<?php

namespace App\Actions\Site;

use App\DTOs\SocketEventDTO;
use App\Enums\SiteStatus;
use App\Events\SocketEvent;
use App\Http\Resources\SiteResource;
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

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $site->server->project_id,
            type: 'site.updated',
            data: new SiteResource($site),
        ));

        dispatch(new CreateJob($site));

        return $site;
    }
}
