<?php

namespace App\ComponentCapabilities;

use App\Actions\Component\RestartComponentWorkers;
use App\Actions\Worker\DeleteWorker;
use App\Models\Component;
use App\Models\Deployment;
use App\RuntimePresets\AbstractRuntimePreset;

class Worker extends AbstractComponentCapability
{
    public static function id(): string
    {
        return 'worker';
    }

    public static function label(): string
    {
        return 'Worker';
    }

    public function description(): string
    {
        return 'Run arbitrary background commands with the process manager.';
    }

    public function requiredServices(AbstractRuntimePreset $preset): array
    {
        return ['process_manager'];
    }

    public function uninstall(Component $component): void
    {
        app(DeleteWorker::class)->deleteForComponent($component);
    }

    public function afterRelease(Component $component, Deployment $deployment): void
    {
        app(RestartComponentWorkers::class)->restart($component);
    }

    public function surfaces(): array
    {
        return ['workers'];
    }
}
