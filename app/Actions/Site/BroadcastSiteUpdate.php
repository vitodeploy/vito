<?php

namespace App\Actions\Site;

use App\DTOs\SocketEventDTO;
use App\Events\SocketEvent;
use App\Http\Resources\SiteResource;
use App\Models\Site;

class BroadcastSiteUpdate
{
    public function broadcast(Site $site): void
    {
        $site->refresh()->load('hostedDomains.ssl', 'workers');

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $site->server->project_id,
            type: 'site.updated',
            data: new SiteResource($site),
        ));
    }
}
