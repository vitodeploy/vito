<?php

namespace App\Actions\Component;

use App\Actions\Network\ResolveServerAddress;
use App\Enums\ComponentStatus;
use App\Enums\LoadBalancerMethod;
use App\Models\Component;
use App\Models\ComponentBackend;

class GenerateLoadBalancerConfig
{
    /** @return array<string, mixed> */
    public function upstream(Component $component): array
    {
        $servers = $component->backends()->with('targetComponent.server', 'targetComponent.capabilities')->get()
            ->filter(fn (ComponentBackend $backend): bool => $backend->target_component_id === null || ($backend->targetComponent !== null
                && ! in_array($backend->targetComponent->status, [ComponentStatus::DELETING, ComponentStatus::DELETION_FAILED], true)
                && $backend->targetComponent->capabilities->contains(fn ($capability): bool => $capability->capability === 'web'
                    && ! in_array($capability->status, [ComponentStatus::DELETING, ComponentStatus::DELETION_FAILED], true))))
            ->flatMap(function (ComponentBackend $backend) use ($component): array {
                $ip = $backend->targetComponent ? (app(ResolveServerAddress::class)->connection($component->server, $backend->targetComponent->server)['ip'] ?? null) : $backend->ip;
                if ($ip === null) {
                    return [];
                }

                return [['ip' => $this->host($ip), 'port' => $backend->port, 'address' => $this->host($ip).':'.$backend->port,
                    'has_weight' => $backend->weight !== null, 'weight' => $backend->weight, 'backup' => $backend->backup]];
            })->values()->all();
        $method = $component->load_balancer_method ?? LoadBalancerMethod::ROUND_ROBIN;

        return [
            'root' => '/var/www/html', 'is_load_balancer' => true, 'has_upstream' => true,
            'upstream_name' => 'component_'.$component->id,
            'upstream_method_least_conn' => $method === LoadBalancerMethod::LEAST_CONNECTIONS,
            'upstream_method_ip_hash' => $method === LoadBalancerMethod::IP_HASH,
            'upstream_servers' => $servers, 'lb_servers' => $servers, 'has_lb_servers' => $servers !== [], 'render_lb_proxy' => $servers !== [],
            'lb_policy' => match ($method) {
                LoadBalancerMethod::LEAST_CONNECTIONS => 'least_conn',
                LoadBalancerMethod::IP_HASH => 'ip_hash',
                LoadBalancerMethod::ROUND_ROBIN => 'round_robin',
            },
        ];
    }

    /** @return list<array<string, mixed>> */
    public function backendBlocks(Component $component): array
    {
        $blocks = [];
        foreach ($component->loadBalancerBackends()->with('component.server')->get()->groupBy('port') as $port => $backends) {
            $listeners = [];
            $proxies = [];
            foreach ($backends as $backend) {
                $connection = app(ResolveServerAddress::class)->connection($backend->component->server, $component->server);
                if ($connection === null) {
                    continue;
                }
                $ip = $connection['ip'];
                $source = $connection['client_ip'];
                $listeners[$ip] = ['ip' => $ip, 'address' => $this->host($ip).':'.$port];
                $proxies[$source] = ['ip' => $source];
            }
            if ($listeners === []) {
                continue;
            }
            $blocks[] = [
                'is_backend' => true, 'backend_key' => 'component_'.$component->id.'_'.$port,
                'backend_https' => '{component_'.$component->id.'_'.$port.'_https}',
                'backend_listeners' => array_values($listeners), 'trusted_proxies' => array_values($proxies),
                'domains' => [['name' => '_', 'address' => 'http://:'.$port]],
            ];
        }

        return $blocks;
    }

    private function host(string $ip): string
    {
        return str_contains($ip, ':') ? '['.$ip.']' : $ip;
    }
}
