<?php

namespace App\Services\VPN;

use App\Models\Network;
use App\Models\NetworkServer;
use App\Services\ServiceInterface;

interface VPN extends ServiceInterface
{
    public function configureNetwork(NetworkServer $membership): void;

    public function removeNetwork(Network $network): void;
}
