<?php

namespace App\Actions\Component;

use App\Enums\ComponentStatus;
use App\Enums\ComponentType;
use App\Enums\ServerStatus;
use App\Models\Component;
use App\Models\ComponentCapability;
use App\Models\Environment;
use App\Models\Server;

class GetComponentOptions
{
    /**
     * @return array{servers: array, capabilities: list<array{id: string, label: string, description: string, form: array}>}
     */
    public function get(Environment $environment): array
    {
        return [
            'servers' => Server::query()->where('project_id', $environment->application->project_id)->where('status', ServerStatus::READY)
                ->orderBy('name')->get()->map(fn (Server $server): array => [
                    'id' => $server->id, 'name' => $server->name, 'ssh_user' => $server->getSshUser(),
                ])->all(),
            'capabilities' => $this->capabilities($environment),
        ];
    }

    /**
     * @return list<array{id: string, label: string, description: string, form: array}>
     */
    public function capabilities(Environment $environment): array
    {
        $capabilities = [];
        if (isset(config('component.runtimes', [])[$environment->application->runtime])) {
            $preset = $environment->application->preset();
            foreach (config('component.capabilities', []) as $id => $configuration) {
                $handler = (new ComponentCapability(['capability' => $id]))->handler();
                $capabilities[] = [
                    'id' => $id,
                    'label' => $configuration['label'],
                    'description' => $handler->description(),
                    'form' => $configuration['form'] ?? $handler->form($preset)->toArray(),
                ];
            }
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
