<?php

namespace App\Actions\Service;

use App\Enums\ServiceStatus;
use App\Exceptions\SSHError;
use App\Models\Service;
use App\Services\SupportsNetworking;
use Illuminate\Support\Facades\Log;

class GetNetworking
{
    /**
     * @return array<string, mixed>
     */
    public function get(Service $service): array
    {
        if (! $service->hasHandler()) {
            return ['supported' => false];
        }

        $handler = $service->handler();

        if (! $handler instanceof SupportsNetworking) {
            return ['supported' => false];
        }

        $details = [
            'supported' => true,
            'pending' => $service->status === ServiceStatus::RESTARTING,
            'failed' => $service->status === ServiceStatus::FAILED,
            ...$handler->networkingDetails(),
        ];

        $secret = $handler->networkingSecret();

        if ($secret !== null) {
            $details['secret'] = $secret;
        }

        try {
            return [...$details, 'effective' => $handler->effectiveNetworking()];
        } catch (SSHError $e) {
            Log::debug('Could not read the networking state of service '.$service->id.': '.$e->getMessage());

            return [...$details, 'effective' => null, 'error' => true];
        }
    }
}
