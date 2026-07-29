<?php

namespace App\Actions\Network;

use App\DTOs\PrivateNetworkDTO;
use App\Enums\FirewallRuleStatus;
use App\Enums\NetworkServerStatus;
use App\Enums\NetworkStatus;
use App\Enums\NetworkType;
use App\Exceptions\PrivateNetworkPersistError;
use App\Exceptions\PrivateNetworkSyncError;
use App\Models\Network;
use App\Models\NetworkServer;
use App\Models\Project;
use App\Models\Server;
use App\Models\ServerProvider;
use App\ServerProviders\ProvidesPrivateNetworks;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncProviderNetworks
{
    public function __construct(
        private ApplyNetworkFirewall $firewall,
        private RecomputeNetworkStatus $recompute,
        private RemoveServerFromNetwork $remove,
        private DeleteNetwork $delete,
    ) {}

    /**
     * A discovered network is recorded as seen before it is reconciled, so that a network the
     * provider reported is never pruned just because persisting it failed.
     *
     * One connection's failure must not suppress pruning for the others, so failures are
     * collected and rethrown once every healthy connection has been processed — the caller
     * still sees the error instead of a silent success. A network that could not be written
     * is counted the same way, so a partial sweep never reports success either.
     *
     * @throws PrivateNetworkSyncError|PrivateNetworkPersistError
     */
    public function forProject(Project $project, ?Network $only = null): void
    {
        if ($only instanceof Network && $only->project_id !== $project->id) {
            return;
        }

        $failure = null;
        $persistFailures = 0;

        foreach ($this->connections($project, $only) as $context) {
            $connection = $context['connection'];

            $asked = $context['servers'] !== []
                && $context['provider']->canDiscoverPrivateNetworks($context['regions'], $context['serversWithoutRegion']);

            try {
                $discovered = $asked
                    ? $context['provider']->privateNetworks(
                        array_map('strval', array_keys($context['servers'])),
                        $context['regions'],
                    )
                    : [];
            } catch (PrivateNetworkSyncError $e) {
                $this->logFailure($e);
                $failure ??= $e;

                continue;
            }

            $seen = [];

            foreach ($discovered as $dto) {
                if ($only instanceof Network && $dto->externalId !== $only->external_id) {
                    continue;
                }

                $seen[] = $dto->externalId;

                if (! $this->reconcile($project, $connection, $dto, $context['servers'])) {
                    $persistFailures++;
                }
            }

            $this->prune($project, $connection, $seen, $only, $asked);
        }

        if ($failure instanceof PrivateNetworkSyncError) {
            throw $failure;
        }

        if ($persistFailures > 0) {
            throw new PrivateNetworkPersistError($project->id, $persistFailures);
        }
    }

    /**
     * Discovered networks are upserted on (project, connection, external id). A per-network DB
     * failure is logged and skipped rather than propagated, so it never counts as a connection
     * failure — that would wrongly suppress pruning for every other network on the connection.
     *
     * @param  array<string, Server>  $servers
     * @return bool whether the network was persisted; the caller fails the sweep at the end
     */
    private function reconcile(Project $project, ServerProvider $connection, PrivateNetworkDTO $dto, array $servers): bool
    {
        try {
            $network = DB::transaction(function () use ($project, $connection, $dto, $servers): Network {
                $network = $this->upsert($project, $connection, $dto);

                $this->reconcileMembers($network, $dto, $servers);

                return $network;
            });
        } catch (QueryException $e) {
            Log::warning('Could not reconcile provider network.', [
                'project_id' => $project->id,
                'server_provider_id' => $connection->id,
                'external_id' => $dto->externalId,
                'reason' => $e->getCode(),
            ]);

            return false;
        }

        $this->firewall->handle($network);
        $this->recompute->handle($network);

        return true;
    }

    private function upsert(Project $project, ServerProvider $connection, PrivateNetworkDTO $dto): Network
    {
        /** @var ?Network $network */
        $network = $project->networks()
            ->where('server_provider_id', $connection->id)
            ->where('external_id', $dto->externalId)
            ->lockForUpdate()
            ->first();

        if (! $network instanceof Network) {
            $network = new Network([
                'project_id' => $project->id,
                'name' => $this->uniqueName($project, $dto->name),
                'type' => NetworkType::PROVIDER,
                'status' => NetworkStatus::SYNCING,
                'cidr' => $dto->cidr,
                'cidr_canonical' => $dto->cidr,
                'region' => $dto->region,
            ]);

            $network->server_provider_id = $connection->id;
            $network->external_id = $dto->externalId;
            $network->last_synced_at = now();
            $network->save();

            $network->firewallRules()->create([
                'name' => 'Allow all',
                'protocol' => null,
                'port' => null,
                'status' => FirewallRuleStatus::READY,
            ]);

            return $network;
        }

        $this->resurrect($network);

        $network->cidr = $dto->cidr;
        $network->cidr_canonical = $dto->cidr;
        $network->region = $dto->region;
        $network->last_synced_at = now();
        $network->save();

        return $network;
    }

    /**
     * A network pruned on an earlier run can legitimately reappear — a transient omission
     * from the provider, or a region that failed and then recovered. `RecomputeNetworkStatus`
     * returns early for DELETING networks without recomputing, and only hard-deletes at zero
     * members, so a resurrected network would otherwise stay DELETING forever with members
     * that never converge, and D2 blocks the user from deleting it.
     */
    private function resurrect(Network $network): void
    {
        if ($network->status !== NetworkStatus::DELETING) {
            return;
        }

        $network->status = NetworkStatus::SYNCING;
        $network->save();

        $network->servers()
            ->where('status', NetworkServerStatus::LEAVING)
            ->update(['status' => NetworkServerStatus::PENDING, 'sync_attempts' => 0]);
    }

    /**
     * Membership is a fact reported by the provider, so members start ACTIVE — there is no
     * on-server provisioning beyond firewall rules. `ApplyNetworkFirewall` downgrades a member
     * to PENDING when its server is unreachable, and the reconciler drives it back up.
     *
     * Departure is keyed on array_key_exists rather than isset, because a member the provider
     * reports without an address is present with a null value.
     *
     * @param  array<string, Server>  $servers
     */
    private function reconcileMembers(Network $network, PrivateNetworkDTO $dto, array $servers): void
    {
        /** @var Collection<int, NetworkServer> $existing */
        $existing = $network->servers()->lockForUpdate()->get()->keyBy('server_id');

        $desired = [];

        foreach ($dto->members as $member) {
            $server = $servers[$member->instanceId] ?? null;

            if ($server instanceof Server) {
                $desired[$server->id] = $member->ip;
            }
        }

        $this->releaseChangedIps($existing, $desired);

        foreach ($desired as $serverId => $ip) {
            /** @var ?NetworkServer $member */
            $member = $existing->get($serverId);

            if (! $member instanceof NetworkServer) {
                $network->servers()->create([
                    'server_id' => $serverId,
                    'ip' => $ip,
                    'status' => NetworkServerStatus::ACTIVE,
                ]);

                continue;
            }

            $member->ip = $ip;

            if ($member->status === NetworkServerStatus::LEAVING) {
                $member->status = NetworkServerStatus::ACTIVE;
                $member->sync_attempts = 0;
            }

            $member->save();
        }

        foreach ($existing as $member) {
            if (array_key_exists($member->server_id, $desired)) {
                continue;
            }

            if ($member->status === NetworkServerStatus::LEAVING) {
                continue;
            }

            $this->remove->remove($member);
        }
    }

    /**
     * `network_servers` carries unique(network_id, ip). When a provider recycles an address
     * onto a different instance within one run, writing the new owner before clearing the old
     * one violates it, so changed addresses are released first.
     *
     * @param  Collection<int, NetworkServer>  $existing
     * @param  array<int, ?string>  $desired
     */
    private function releaseChangedIps(Collection $existing, array $desired): void
    {
        foreach ($existing as $member) {
            $target = $desired[$member->server_id] ?? null;

            if ($member->ip !== null && $member->ip !== $target) {
                $member->ip = null;
                $member->save();
            }
        }
    }

    /**
     * `$asked` is false when the connection had no instance ids to query, or when the provider
     * could not be queried at all (an EC2 connection whose servers carry no region), so `$seen`
     * being empty carries no information about what still exists at the provider. Only a network
     * with no members left may be reaped in that case — anything else has to wait for a run
     * that could actually ask.
     *
     * @param  array<int, string>  $seen
     */
    private function prune(Project $project, ServerProvider $connection, array $seen, ?Network $only, bool $asked): void
    {
        $project->networks()
            ->where('type', NetworkType::PROVIDER)
            ->where('server_provider_id', $connection->id)
            ->where('status', '!=', NetworkStatus::DELETING)
            ->when($only instanceof Network, fn ($query) => $query->whereKey($only?->id))
            ->get()
            ->each(function (Network $network) use ($seen, $asked): void {
                if (! $asked) {
                    if ($this->hasLiveMembers($network)) {
                        return;
                    }

                    $this->delete->delete($network);

                    return;
                }

                $stillPresent = in_array($network->external_id, $seen, true);

                if ($stillPresent && $this->hasLiveMembers($network)) {
                    return;
                }

                $this->delete->delete($network);
            });
    }

    private function hasLiveMembers(Network $network): bool
    {
        return $network->servers()
            ->where('status', '!=', NetworkServerStatus::LEAVING)
            ->exists();
    }

    private function uniqueName(Project $project, string $name): string
    {
        $name = trim($name) !== '' ? trim($name) : 'network';
        $candidate = $name;
        $suffix = 2;

        while ($project->networks()->where('name', $candidate)->exists()) {
            $candidate = $name.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    /**
     * @return array<int, array{connection: ServerProvider, provider: ProvidesPrivateNetworks, key: string, servers: array<string, Server>, regions: array<int, string>, serversWithoutRegion: int}>
     */
    private function connections(Project $project, ?Network $only): array
    {
        /** @var Collection<int, Server> $servers */
        $servers = $project->servers()
            ->whereNotNull('provider_id')
            ->when(
                $only instanceof Network,
                fn ($query) => $query->where('provider_id', $only?->server_provider_id)
            )
            ->with('serverProvider')
            ->get();

        $contexts = [];

        foreach ($servers as $server) {
            $connection = $server->serverProvider;

            if (! isset($contexts[$connection->id])) {
                $provider = $connection->provider();

                if (! $provider instanceof ProvidesPrivateNetworks) {
                    continue;
                }

                $contexts[$connection->id] = [
                    'connection' => $connection,
                    'provider' => $provider,
                    'key' => $provider->instanceIdKey(),
                    'servers' => [],
                    'regions' => [],
                    'serversWithoutRegion' => 0,
                ];
            }

            $instanceId = $server->provider_data[$contexts[$connection->id]['key']] ?? null;

            if ($instanceId === null || $instanceId === '') {
                continue;
            }

            $contexts[$connection->id]['servers'][(string) $instanceId] = $server;

            $region = $server->provider_data['region'] ?? null;

            if (! is_string($region) || $region === '') {
                $contexts[$connection->id]['serversWithoutRegion']++;

                continue;
            }

            if (! in_array($region, $contexts[$connection->id]['regions'], true)) {
                $contexts[$connection->id]['regions'][] = $region;
            }
        }

        foreach ($this->orphanedConnections($project, $only, array_keys($contexts)) as $connection) {
            $provider = $connection->provider();

            if (! $provider instanceof ProvidesPrivateNetworks) {
                continue;
            }

            $contexts[$connection->id] = [
                'connection' => $connection,
                'provider' => $provider,
                'key' => $provider->instanceIdKey(),
                'servers' => [],
                'regions' => [],
                'serversWithoutRegion' => 0,
            ];
        }

        return array_values($contexts);
    }

    /**
     * A connection whose last managed server has been deleted still needs a pass. Its provider
     * networks have no members left, so nothing can reconcile them, and because they are
     * provider-managed the policy also refuses a manual delete — without this they would be
     * stranded permanently. There is nothing to ask the provider in that case, so the context
     * carries no servers and `forProject()` goes straight to pruning.
     *
     * @param  array<int, int>  $known
     * @return Collection<int, ServerProvider>
     */
    private function orphanedConnections(Project $project, ?Network $only, array $known): Collection
    {
        $ids = $project->networks()
            ->where('type', NetworkType::PROVIDER)
            ->where('status', '!=', NetworkStatus::DELETING)
            ->whereNotNull('server_provider_id')
            ->when($only instanceof Network, fn ($query) => $query->whereKey($only?->id))
            ->pluck('server_provider_id')
            ->unique()
            ->reject(fn (int $id): bool => in_array($id, $known, true))
            ->values()
            ->all();

        if ($ids === []) {
            return new Collection;
        }

        return ServerProvider::query()->whereIn('id', $ids)->get();
    }

    private function logFailure(PrivateNetworkSyncError $e): void
    {
        Log::warning('Provider private network sync failed.', [
            'server_provider_id' => $e->serverProviderId,
            'provider' => $e->provider,
            'profile' => $e->profile,
            'status' => $e->status,
            'region' => $e->region,
            'permission_error' => $e->isPermissionError(),
        ]);
    }
}
