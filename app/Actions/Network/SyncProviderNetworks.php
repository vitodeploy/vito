<?php

namespace App\Actions\Network;

use App\DTOs\PrivateNetworkDTO;
use App\Enums\FirewallRuleStatus;
use App\Enums\NetworkServerStatus;
use App\Enums\NetworkStatus;
use App\Enums\NetworkType;
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
    ) {}

    /**
     * A discovered network is recorded as seen before it is reconciled, so that a network the
     * provider reported is never pruned just because persisting it failed.
     */
    public function forProject(Project $project, ?Network $only = null): void
    {
        if ($only instanceof Network && $only->project_id !== $project->id) {
            return;
        }

        foreach ($this->connections($project, $only) as $context) {
            $connection = $context['connection'];

            try {
                $discovered = $context['provider']->privateNetworks(
                    array_map('strval', array_keys($context['servers'])),
                    $context['regions'],
                );
            } catch (PrivateNetworkSyncError $e) {
                $this->logFailure($e);

                continue;
            }

            $seen = [];

            foreach ($discovered as $dto) {
                if ($only instanceof Network && $dto->externalId !== $only->external_id) {
                    continue;
                }

                $seen[] = $dto->externalId;

                $this->reconcile($project, $connection, $dto, $context['servers']);
            }

            $this->prune($project, $connection, $seen, $only);
        }
    }

    /**
     * Discovered networks are upserted on (project, connection, external id). A per-network DB
     * failure is logged and skipped rather than propagated, so it never counts as a connection
     * failure — that would wrongly suppress pruning for every other network on the connection.
     *
     * @param  array<string, Server>  $servers
     */
    private function reconcile(Project $project, ServerProvider $connection, PrivateNetworkDTO $dto, array $servers): void
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

            return;
        }

        $this->firewall->handle($network);
        $this->recompute->handle($network);
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
     * @param  array<int, string>  $seen
     */
    private function prune(Project $project, ServerProvider $connection, array $seen, ?Network $only): void
    {
        $project->networks()
            ->where('type', NetworkType::PROVIDER)
            ->where('server_provider_id', $connection->id)
            ->where('status', '!=', NetworkStatus::DELETING)
            ->when($only instanceof Network, fn ($query) => $query->whereKey($only?->id))
            ->get()
            ->each(function (Network $network) use ($seen): void {
                $stillPresent = in_array($network->external_id, $seen, true);

                if ($stillPresent && $this->hasLiveMembers($network)) {
                    return;
                }

                app(DeleteNetwork::class)->delete($network);
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
     * @return array<int, array{connection: ServerProvider, provider: ProvidesPrivateNetworks, key: string, servers: array<string, Server>, regions: array<int, string>}>
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
                ];
            }

            $instanceId = $server->provider_data[$contexts[$connection->id]['key']] ?? null;

            if ($instanceId === null || $instanceId === '') {
                continue;
            }

            $contexts[$connection->id]['servers'][(string) $instanceId] = $server;

            $region = $server->provider_data['region'] ?? null;

            if (is_string($region) && $region !== '' && ! in_array($region, $contexts[$connection->id]['regions'], true)) {
                $contexts[$connection->id]['regions'][] = $region;
            }
        }

        return array_values(array_filter(
            $contexts,
            fn (array $context): bool => $context['servers'] !== []
        ));
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
