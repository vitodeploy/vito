<?php

namespace App\Actions\Component;

use App\Actions\FirewallRule\ManageRule;
use App\Actions\Network\ResolveServerAddress;
use App\DTOs\SocketEventDTO;
use App\Enums\ComponentStatus;
use App\Enums\ComponentType;
use App\Enums\FirewallRuleStatus;
use App\Events\SocketEvent;
use App\Http\Resources\FirewallRuleResource;
use App\Jobs\Component\SyncLoadBalancerServerJob;
use App\Models\Component;
use App\Models\ComponentBackend;
use App\Models\NetworkServer;
use App\Models\Server;
use App\Services\Firewall\Firewall;
use App\Support\Cidr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SyncLoadBalancerBackends
{
    private const ERROR = 'Load balancer synchronization failed. Check server connectivity and private networks, then retry the failed queue job or save the backends again.';

    private const NETWORK_ERROR = 'Some backends have no available private network and are not receiving traffic. Restore the network or update the backends.';

    /** @param list<int> $serverIds */
    public function dispatch(array $serverIds): void
    {
        foreach (array_unique($serverIds) as $serverId) {
            SyncLoadBalancerServerJob::dispatch($serverId)->afterCommit();
        }
    }

    public function membershipChanged(NetworkServer $member): void
    {
        if ($member->routingChanged()) {
            DB::afterCommit(fn () => $this->changedServer($member->server_id));
        }
    }

    public function changedServer(int $serverId): void
    {
        $backends = ComponentBackend::query()->where(fn ($query) => $query
            ->whereHas('component', fn ($query) => $query->where('server_id', $serverId))
            ->orWhereHas('targetComponent', fn ($query) => $query->where('server_id', $serverId)))
            ->with('component', 'targetComponent')->get();
        if ($backends->isNotEmpty()) {
            $this->dispatch($this->servers($backends));
        }
    }

    /**
     * @param  Collection<int, ComponentBackend>  $backends
     * @return list<int>
     */
    public function servers(Collection $backends): array
    {
        return $backends->flatMap(fn (ComponentBackend $backend): array => [$backend->component->server_id, $backend->targetComponent?->server_id])
            ->filter()->unique()->values()->all();
    }

    public function remove(Component $component): void
    {
        DB::transaction(function () use ($component): void {
            $backends = ComponentBackend::query()->where('component_id', $component->id)->orWhere('target_component_id', $component->id)
                ->with('component', 'targetComponent')->get();
            $servers = $this->servers($backends);
            ComponentBackend::query()->whereKey($backends->modelKeys())->delete();
            $this->dispatch($servers);
        });
    }

    public function sync(Server $server): void
    {
        DB::transaction(function () use ($server): void {
            Server::query()->lockForUpdate()->findOrFail($server->id);
            $backends = ComponentBackend::query()->whereHas('targetComponent', fn ($query) => $query->where('server_id', $server->id))
                ->with('component.server', 'targetComponent', 'firewallRule')->get();
            $rules = app(ManageRule::class);
            $ids = [];
            foreach ($backends as $backend) {
                $source = app(ResolveServerAddress::class)->connection($server, $backend->component->server)['ip'] ?? null;
                if ($source === null) {
                    continue;
                }
                $input = ['name' => 'LB backend', 'type' => 'allow', 'protocol' => 'tcp', 'port' => $backend->port,
                    'source' => $source, 'source_any' => false, 'mask' => Cidr::hostPrefix($source)];
                $rule = $backend->firewallRule;
                if ($rule) {
                    $rules->update($rule, $input);
                } else {
                    $rule = $rules->create($server, $input, componentManaged: true);
                    $backend->firewall_rule_id = $rule->id;
                    $backend->save();
                }
                $ids[] = $rule->id;
            }
            foreach ($server->firewallRules()->where('component_managed', true)->whereNull('environment_attachment_id')->whereNotIn('id', $ids)->get() as $rule) {
                $rules->delete($rule);
            }
        });
        $managed = $server->firewallRules()->where('component_managed', true)->whereNull('environment_attachment_id')->get();
        if ($managed->isNotEmpty()) {
            /** @var Firewall $firewall */
            $firewall = $server->firewall()->handler();
            $firewall->applyRules();
        }
        foreach ($server->components()->where(fn ($query) => $query->where('type', ComponentType::LOAD_BALANCER)
            ->orWhereHas('capabilities', fn ($query) => $query->where('capability', 'web')))->get() as $component) {
            app(SyncComponentVhost::class)->sync($component);
            $unavailable = $component->backends()->with('targetComponent.server')->get()->contains(fn (ComponentBackend $backend): bool => $backend->targetComponent !== null
                && app(ResolveServerAddress::class)->connection($server, $backend->targetComponent->server) === null);
            if ($unavailable || in_array($component->last_error, [self::ERROR, self::NETWORK_ERROR], true)) {
                $component->last_error = $unavailable ? self::NETWORK_ERROR : null;
                $component->save();
            }
            app(BroadcastComponentUpdate::class)->broadcast($component);
        }
        $deleting = $managed->where('status', FirewallRuleStatus::DELETING)->modelKeys();
        if ($deleting !== []) {
            DB::transaction(function () use ($server, $deleting): void {
                $server->firewallRules()->whereKey($deleting)->delete();
                /** @var Firewall $firewall */
                $firewall = $server->firewall()->handler();
                $firewall->applyRules();
            });
        }
        $server->firewallRules()->where('component_managed', true)->whereNull('environment_attachment_id')->update(['status' => FirewallRuleStatus::READY]);
        foreach ($deleting as $id) {
            SocketEvent::dispatch(new SocketEventDTO(projectId: $server->project_id, type: 'firewall-rule.deleted', data: ['id' => $id]));
        }
        foreach ($server->firewallRules()->where('component_managed', true)->whereNull('environment_attachment_id')->get() as $rule) {
            SocketEvent::dispatch(new SocketEventDTO(projectId: $server->project_id, type: 'firewall-rule.updated', data: new FirewallRuleResource($rule)));
        }
    }

    public function failed(int $serverId): void
    {
        foreach (Component::query()->where('server_id', $serverId)->whereNotIn('status', [ComponentStatus::DELETING, ComponentStatus::DELETION_FAILED])
            ->where(fn ($query) => $query->where('type', ComponentType::LOAD_BALANCER)->orWhereHas('loadBalancerBackends'))->get() as $component) {
            $component->last_error = self::ERROR;
            $component->save();
            app(BroadcastComponentUpdate::class)->broadcast($component);
        }
    }
}
