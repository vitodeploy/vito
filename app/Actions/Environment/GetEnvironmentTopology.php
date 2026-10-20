<?php

namespace App\Actions\Environment;

use App\Contracts\VitoEnum;
use App\Enums\ComponentType;
use App\Models\Environment;

/**
 * @phpstan-type TopologyNode array{id: string, type: string, name: string, server_id: ?int, status: string, status_color: string, url: string, capabilities: list<string>, needs_redeploy: bool}
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
        $baseUrl = route('environments.show', [$environment->application_id, $environment->id], false);
        $nodes = [];
        $edges = [];

        foreach ($components->pluck('server')->merge($attachments->pluck('server'))->unique('id')->sortBy('id') as $server) {
            $nodes[] = $this->node('server-'.$server->id, 'server', $server->name, $server->id, $server->status, route('servers.show', $server, false));
        }
        foreach ($components as $component) {
            $id = 'component-'.$component->id;
            $url = $baseUrl.'/components/'.$component->id;
            $nodes[] = $this->node($id, $component->type->getText(), $component->name, $component->server_id, $component->status, $url,
                $component->capabilities->pluck('capability')->sort()->values()->all(), $component->needs_redeploy);

            foreach ($component->backends as $backend) {
                if ($backend->target_component_id !== null) {
                    if (! $components->has($backend->target_component_id)) {
                        continue;
                    }
                    $target = 'component-'.$backend->target_component_id;
                } else {
                    $target = 'external-'.$backend->id;
                    $host = str_contains($backend->ip ?? '', ':') ? '['.$backend->ip.']' : $backend->ip;
                    $nodes[] = $this->node($target, 'external', $host.':'.$backend->port, null, null, $url);
                }
                $edges[] = ['id' => 'backend-'.$backend->id, 'source' => $id, 'target' => $target];
            }
        }
        foreach ($environment->domains()->orderBy('id')->get() as $domain) {
            $component = $components->get($domain->component_id);
            if (! $component) {
                continue;
            }
            $id = 'domain-'.$domain->id;
            $nodes[] = $this->node($id, 'domain', $domain->host, $component->server_id, $domain->status, $baseUrl.'/domains');
            $edges[] = ['id' => $id, 'source' => $id, 'target' => 'component-'.$component->id];
        }
        foreach ($attachments as $attachment) {
            $id = 'resource-'.$attachment->id;
            $name = $attachment->attachable->name ?? 'Unavailable resource';
            $nodes[] = $this->node($id, $attachment->kind(), $attachment->alias ? $attachment->alias.' · '.$name : $name,
                $attachment->server_id, $attachment->status, $baseUrl.'/resources');
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
     * @return TopologyNode
     */
    private function node(string $id, string $type, string $name, ?int $serverId, ?VitoEnum $status, string $url, array $capabilities = [], bool $needsRedeploy = false): array
    {
        return [
            'id' => $id,
            'type' => $type,
            'name' => $name,
            'server_id' => $serverId,
            'status' => $status?->getText() ?? 'unmonitored',
            'status_color' => $status?->getColor() ?? 'gray',
            'url' => $url,
            'capabilities' => $capabilities,
            'needs_redeploy' => $needsRedeploy,
        ];
    }
}
