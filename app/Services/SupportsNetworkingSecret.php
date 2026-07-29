<?php

namespace App\Services;

use App\Exceptions\SSHError;

interface SupportsNetworkingSecret extends SupportsNetworking
{
    public function generateNetworkingSecret(): string;

    /**
     * @throws SSHError
     */
    public function writeNetworkingSecret(?string $secret): void;
}
