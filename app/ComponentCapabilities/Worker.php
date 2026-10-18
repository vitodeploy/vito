<?php

namespace App\ComponentCapabilities;

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

    public function uninstall(\App\Models\Component $component): void
    {
        app(\App\Actions\Worker\DeleteWorker::class)->deleteForComponent($component);
    }

    public function afterRelease(\App\Models\Component $component, \App\Models\Deployment $deployment): void
    {
        app(\App\Actions\Component\RestartComponentWorkers::class)->restart($component);
    }

    public function surfaces(): array
    {
        return ['workers'];
    }
}
