<?php

namespace App\Actions\Network;

use App\Enums\NetworkType;
use App\Models\Network;
use Illuminate\Validation\ValidationException;

class AllocateWireGuardPort
{
    /**
     * @param  array<int, int>  $serverIds
     */
    public function allocate(int $projectId, array $serverIds, int $requested, ?int $excludeNetworkId = null): int
    {
        $used = Network::query()
            ->where('type', NetworkType::WIREGUARD)
            ->where('project_id', $projectId)
            ->when($excludeNetworkId !== null, fn ($query) => $query->whereKeyNot($excludeNetworkId))
            ->whereHas('servers', fn ($query) => $query->whereIn('server_id', $serverIds))
            ->pluck('port')
            ->filter()
            ->all();

        $port = $requested;

        while (in_array($port, $used, true)) {
            $port++;

            if ($port > 65535) {
                throw ValidationException::withMessages([
                    'port' => __('No free WireGuard port is available for the selected servers.'),
                ]);
            }
        }

        return $port;
    }
}
