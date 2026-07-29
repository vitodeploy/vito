<?php

namespace App\Actions\Service;

use App\Enums\ServiceStatus;
use App\Models\Service;
use App\Services\SupportsNetworking;

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
            ...$handler->networkingDetails(),
        ];

        $secret = $handler->networkingSecret();

        if ($secret !== null) {
            $details['secret'] = $secret;
        }

        return $details;
    }
}
