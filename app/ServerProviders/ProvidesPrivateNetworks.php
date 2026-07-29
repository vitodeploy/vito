<?php

namespace App\ServerProviders;

use App\DTOs\PrivateNetworkDTO;
use App\Exceptions\PrivateNetworkSyncError;

/**
 * Opt-in capability. Providers implementing this can be asked, at connection level,
 * which private networks a set of instances belong to.
 *
 * Implementations are resolved via `ServerProvider::provider()`, which constructs
 * with an empty `Server`. They must therefore read credentials from
 * `$this->serverProvider->getCredentials()` and never touch `$this->server`.
 */
interface ProvidesPrivateNetworks
{
    /**
     * The `servers.provider_data` key holding this provider's instance identifier.
     */
    public function instanceIdKey(): string;

    /**
     * Whether a complete query is possible with the given regions. A provider that returns
     * false cannot be asked, so an empty result must not be read as "these networks are gone".
     *
     * `$serversWithoutRegion` counts the connection's servers that carry an instance id but no
     * region, whose networks a regional provider would therefore never see.
     *
     * @param  array<int, string>  $regions
     */
    public function canDiscoverPrivateNetworks(array $regions, int $serversWithoutRegion): bool;

    /**
     * Private networks that at least one of $instanceIds is attached to. Members are
     * restricted to $instanceIds — instances the caller did not ask about are omitted.
     *
     * @param  array<int, string>  $instanceIds
     * @param  array<int, string>  $regions  regions to query; ignored by global providers
     * @return array<int, PrivateNetworkDTO>
     *
     * @throws PrivateNetworkSyncError
     */
    public function privateNetworks(array $instanceIds, array $regions): array;
}
