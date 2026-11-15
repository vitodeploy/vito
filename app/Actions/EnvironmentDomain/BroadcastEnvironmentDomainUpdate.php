<?php

namespace App\Actions\EnvironmentDomain;

use App\DTOs\SocketEventDTO;
use App\Events\SocketEvent;
use App\Http\Resources\EnvironmentDomainResource;
use App\Models\EnvironmentDomain;
use Illuminate\Support\Facades\DB;

class BroadcastEnvironmentDomainUpdate
{
    public function broadcast(EnvironmentDomain $domain): void
    {
        $event = new SocketEventDTO(
            projectId: $domain->environment->application->project_id,
            type: 'environment-domain.updated',
            data: new EnvironmentDomainResource($domain),
        );
        DB::afterCommit(static fn () => SocketEvent::dispatch($event));
    }
}
