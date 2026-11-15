<?php

namespace App\ServerProviders;

use App\DTOs\PrivateNetworkDTO;
use App\Exceptions\PrivateNetworkSyncError;

interface ProvidesPrivateNetworks
{
    public function instanceIdKey(): string;

    /**
     * @param  array<int, string>  $regions
     */
    public function canDiscoverPrivateNetworks(array $regions, int $serversWithoutRegion): bool;

    /**
     * @param  array<int, string>  $instanceIds
     * @param  array<int, string>  $regions
     * @return array<int, PrivateNetworkDTO>
     *
     * @throws PrivateNetworkSyncError
     */
    public function privateNetworks(array $instanceIds, array $regions): array;
}
