<?php

namespace App\Actions\Component;

use App\Enums\ComponentStatus;
use App\Enums\ComponentType;
use App\Enums\ServerStatus;
use App\Enums\ServiceStatus;
use App\Models\Component;
use App\Models\ComponentCapability;
use App\Models\Environment;
use App\Models\Server;
use App\Models\Service;
use App\RuntimePresets\AbstractRuntimePreset;

class GetComponentOptions
{
    /**
     * @return array{servers: array, capabilities: list<array{id: string, label: string, description: string, form: array}>}
     */
    public function get(Environment $environment): array
    {
        return [
            'servers' => $this->servers($environment->application->project_id),
            'component_names' => $environment->components()->pluck('name')->all(),
            'capabilities' => $this->capabilities($environment),
        ];
    }

    /**
     * Ready project servers with what is installed on them. `database` names the service a new
     * resource can use, which needs a ready firewall as well as a ready database.
     *
     * @return list<array{id: int, name: string, ssh_user: string, services: array<string, list<string>>, database: ?string}>
     */
    public function servers(int $projectId): array
    {
        return Server::query()->where('project_id', $projectId)->where('status', ServerStatus::READY)->with('services')
            ->orderBy('name')->get()->map(function (Server $server): array {
                $installed = $server->services->whereIn('status', [ServiceStatus::READY, ServiceStatus::STOPPED, ServiceStatus::DISABLED]);
                $ready = fn (string $type): ?Service => $server->services->where('type', $type)->where('status', ServiceStatus::READY)->first();

                return [
                    'id' => $server->id, 'name' => $server->name, 'ssh_user' => $server->getSshUser(),
                    'services' => $installed->groupBy('type')->map(fn ($services): array => $services->pluck('version')->values()->all())->all(),
                    'database' => $ready('firewall') ? $ready('database')?->name : null,
                ];
            })->all();
    }

    /**
     * @return list<array{id: string, label: string, description: string, form: array}>
     */
    public function capabilities(Environment $environment): array
    {
        $preset = $environment->application->presetOrNull();

        return $preset ? $this->forPreset($preset) : [];
    }

    /**
     * @return list<array{id: string, label: string, description: string, form: array}>
     */
    public function forPreset(AbstractRuntimePreset $preset): array
    {
        $capabilities = [];
        foreach (config('component.capabilities', []) as $id => $configuration) {
            $handler = (new ComponentCapability(['capability' => $id]))->handler();
            $capabilities[] = [
                'id' => $id,
                'label' => $configuration['label'],
                'description' => $handler->description(),
                'form' => $configuration['form'] ?? $handler->form($preset)->toArray(),
            ];
        }

        return $capabilities;
    }

    /** @return list<array{id: int, name: string, server_name: string}> */
    public function backendTargets(Environment $environment): array
    {
        return $environment->components()->where('type', ComponentType::APP)->where('status', ComponentStatus::READY)
            ->whereHas('capabilities', fn ($query) => $query->where('capability', 'web')->where('status', ComponentStatus::READY))
            ->with('server')->orderBy('name')->get()->map(fn (Component $component): array => [
                'id' => $component->id, 'name' => $component->name, 'server_name' => $component->server->name,
            ])->all();
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    public function primaryCandidates(Environment $environment): array
    {
        return $environment->components()->where('type', ComponentType::APP)
            ->whereHas('server', fn ($query) => $query->where('project_id', $environment->application->project_id))
            ->whereNotIn('status', [ComponentStatus::DELETING, ComponentStatus::DELETION_FAILED])->orderBy('id')
            ->get()->map(fn (Component $component): array => ['id' => $component->id, 'name' => $component->name])->all();
    }
}
