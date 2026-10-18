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
     * @return array{servers: array, capabilities: array}
     */
    public function get(Environment $environment): array
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

        return [
            'servers' => Server::query()->where('project_id', $environment->application->project_id)->where('status', ServerStatus::READY)
                ->orderBy('name')->get()->map(fn (Server $server): array => [
                    'id' => $server->id, 'name' => $server->name, 'ssh_user' => $server->getSshUser(),
                ])->all(),
            'capabilities' => $capabilities,
        ];
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    public function primaryCandidates(Environment $environment): array
    {
        return $environment->components()->where('type', ComponentType::APP)
            ->whereNotIn('status', [ComponentStatus::DELETING, ComponentStatus::DELETION_FAILED])->orderBy('id')
            ->get()->map(fn (Component $component): array => ['id' => $component->id, 'name' => $component->name])->all();
    }
}
