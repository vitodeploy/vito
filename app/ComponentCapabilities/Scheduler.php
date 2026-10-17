<?php

namespace App\ComponentCapabilities;

use App\Actions\ComponentCapability\ValidateSchedulerCapability;
use App\DTOs\DynamicField;
use App\DTOs\DynamicForm;
use App\Models\Component;
use App\Models\Server;
use App\RuntimePresets\AbstractRuntimePreset;

class Scheduler extends AbstractComponentCapability
{
    public static function id(): string
    {
        return 'scheduler';
    }

    public static function label(): string
    {
        return 'Scheduler';
    }

    public function description(): string
    {
        return 'Run a scheduled command once per environment.';
    }

    public function validate(Component $component, Server $server): void
    {
        parent::validate($component, $server);

        app(ValidateSchedulerCapability::class)->validate($this->capability, $component);
    }

    public function surfaces(): array
    {
        return ['cron_jobs'];
    }

    public function form(AbstractRuntimePreset $preset): DynamicForm
    {
        return DynamicForm::make([
            DynamicField::make('command')
                ->text()
                ->label('Command')
                ->default($preset->schedulerCommand()),
        ]);
    }
}
