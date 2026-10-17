<?php

namespace App\ComponentCapabilities;

use App\Actions\ComponentCapability\ValidateWebCapability;
use App\DTOs\DynamicField;
use App\DTOs\DynamicForm;
use App\Enums\WebMode;
use App\Models\Component;
use App\Models\Server;
use App\RuntimePresets\AbstractRuntimePreset;

class Web extends AbstractComponentCapability
{
    public static function id(): string
    {
        return 'web';
    }

    public static function label(): string
    {
        return 'Web';
    }

    public function description(): string
    {
        return 'Serve HTTP traffic through the server webserver.';
    }

    public function requiredServices(AbstractRuntimePreset $preset): array
    {
        return match ($preset->webMode()) {
            WebMode::FPM => ['webserver', 'php'],
            WebMode::PROXY => ['webserver', 'process_manager'],
            WebMode::STATIC => ['webserver'],
        };
    }

    public function validate(Component $component, Server $server): void
    {
        parent::validate($component, $server);

        app(ValidateWebCapability::class)->validate($this->capability, $component, $server);
    }

    public function surfaces(): array
    {
        return ['domains'];
    }

    public function form(AbstractRuntimePreset $preset): DynamicForm
    {
        $fields = [
            DynamicField::make('web_directory')
                ->text()
                ->label('Web Directory')
                ->default($preset->webRoot())
                ->description('Relative to the release directory. Leave empty for the release root.'),
        ];

        if ($preset->webMode() === WebMode::PROXY) {
            $fields[] = DynamicField::make('start_command')
                ->text()
                ->label('Start Command')
                ->default($preset->startCommand())
                ->description('The application must listen on the PORT environment variable.');
            $fields[] = DynamicField::make('port')
                ->text()
                ->label('Port')
                ->description('Leave empty to assign an available port automatically.');
        }

        return DynamicForm::make($fields);
    }
}
