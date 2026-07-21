<?php

namespace App\Actions\Network;

use App\DTOs\SocketEventDTO;
use App\Enums\NetworkServerStatus;
use App\Enums\NetworkStatus;
use App\Events\SocketEvent;
use App\Http\Resources\NetworkResource;
use App\Models\Network;

class RecomputeNetworkStatus
{
    public function handle(Network $network): void
    {
        $network = $network->fresh();

        if (! $network instanceof Network) {
            return;
        }

        if ($network->status === NetworkStatus::DELETING) {
            if ($network->servers()->count() === 0) {
                $projectId = $network->project_id;
                $id = $network->id;
                $network->delete();

                SocketEvent::dispatch(new SocketEventDTO(
                    projectId: $projectId,
                    type: 'network.deleted',
                    data: ['id' => $id],
                ));
            }

            return;
        }

        $statuses = $network->servers()->pluck('status');

        $computed = match (true) {
            $statuses->contains(NetworkServerStatus::FAILED) => NetworkStatus::FAILED,
            $statuses->contains(NetworkServerStatus::PENDING),
            $statuses->contains(NetworkServerStatus::UPDATING),
            $statuses->contains(NetworkServerStatus::LEAVING) => NetworkStatus::SYNCING,
            default => NetworkStatus::ACTIVE,
        };

        if ($network->status !== $computed) {
            $network->status = $computed;
            $network->save();
        }

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $network->project_id,
            type: 'network.updated',
            data: new NetworkResource($network->loadCount('servers')),
        ));
    }
}
