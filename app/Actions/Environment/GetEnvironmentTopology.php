<?php

namespace App\Actions\Environment;

use App\Contracts\VitoEnum;
use App\Enums\ComponentType;
use App\Enums\HostedDomainType;
use App\Models\Environment;

/**
 * @phpstan-type TopologyNode array{id: string, type: string, name: string, record_id: ?int, server_id: ?int, server_name: ?string, status: string, status_color: string, url: string, capabilities: list<string>, domains: list<string>, needs_redeploy: bool}
 */
class GetEnvironmentTopology
{
    /** @return array{nodes: list<TopologyNode>, edges: list<array{id: string, source: string, target: string}>} */
    public function get(Environment $environment): array
    {
        $projectId = $environment->application->project_id;
        $components = $environment->components()->whereHas('server', fn ($query) => $query->where('project_id', $projectId))
            ->with('server', 'capabilities', 'backends')->orderBy('id')->get()->keyBy('id');
        $attachments = $environment->attachments()->whereHas('server', fn ($query) => $query->where('project_id', $projectId))
            ->with('server', 'attachable')->orderBy('id')->get();
        $domains = $environment->domains()->where('type', '!=', HostedDomainType::REDIRECT)->orderBy('id')->get()->groupBy('component_id');
        $baseUrl = route('environments.show', [$environment->application_id, $environment->id], false);
        $nodes = [];
        $edges = [];

        foreach ($components as $component) {
            $id = 'component-'.$component->id;
            $url = $baseUrl.'?component='.$component->id;
            $nodes[] = $this->node($id, $component->type->getText(), $component->name, $component->id, $component->server_id, $component->server?->name, $component->status, $url,
                $component->capabilities->pluck('capability')->sort()->values()->all(), $domains->get($component->id)?->pluck('host')->all() ?? [], $component->needs_redeploy);

            foreach ($component->backends as $backend) {
                if ($backend->target_component_id !== null) {
                    if (! $components->has($backend->target_component_id)) {
                        continue;
                    }
                    $target = 'component-'.$backend->target_component_id;
                } else {
                    $target = 'external-'.$backend->id;
                    $host = str_contains($backend->ip ?? '', ':') ? '['.$backend->ip.']' : $backend->ip;
                    $nodes[] = $this->node($target, 'external', $host.':'.$backend->port, recordId: null, serverId: null, serverName: null, status: null, url: $url);
                }
                $edges[] = ['id' => 'backend-'.$backend->id, 'source' => $id, 'target' => $target];
            }
        }
        foreach ($attachments as $attachment) {
            $id = 'resource-'.$attachment->id;
            $name = $attachment->attachable->name ?? 'Unavailable resource';
            $nodes[] = $this->node($id, $attachment->kind(), $attachment->alias ? $attachment->alias.' · '.$name : $name,
                $attachment->id, $attachment->server_id, $attachment->server?->name, $attachment->status, $baseUrl.'?resource='.$attachment->id);
            foreach ($components as $component) {
                if ($component->type === ComponentType::APP) {
                    $edges[] = ['id' => 'component-'.$component->id.'-'.$id, 'source' => 'component-'.$component->id, 'target' => $id];
                }
            }
        }

        return ['nodes' => $nodes, 'edges' => $edges];
    }

    /**
     * @param  list<string>  $capabilities
     * @param  list<string>  $domains
     * @return TopologyNode
     */
    private function node(string $id, string $type, string $name, ?int $recordId, ?int $serverId, ?string $serverName, ?VitoEnum $status, string $url, array $capabilities = [], array $domains = [], bool $needsRedeploy = false): array
    {
        return [
            'id' => $id,
            'type' => $type,
            'name' => $name,
            'record_id' => $recordId,
            'server_id' => $serverId,
            'server_name' => $serverName,
            'status' => $status?->getText() ?? 'unmonitored',
            'status_color' => $status?->getColor() ?? 'gray',
            'url' => $url,
            'capabilities' => $capabilities,
            'domains' => $domains,
            'needs_redeploy' => $needsRedeploy,
        ];
    }
}
