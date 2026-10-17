<?php

namespace App\ComponentCapabilities;

use App\Actions\ComponentCapability\ValidateCapabilityServices;
use App\DTOs\DynamicForm;
use App\Models\Component;
use App\Models\ComponentCapability;
use App\Models\Server;
use App\RuntimePresets\AbstractRuntimePreset;
use Illuminate\Validation\ValidationException;

abstract class AbstractComponentCapability
{
    public function __construct(protected ComponentCapability $capability) {}

    abstract public static function id(): string;

    abstract public static function label(): string;

    public function description(): string
    {
        return '';
    }

    /**
     * @return list<string>
     */
    public function requiredServices(AbstractRuntimePreset $preset): array
    {
        return [];
    }

    /**
     * @throws ValidationException
     */
    public function validate(Component $component, Server $server): void
    {
        app(ValidateCapabilityServices::class)->validate($this->capability, $component, $server);
    }

    /**
     * @return list<string>
     */
    public function surfaces(): array
    {
        return [];
    }

    public function form(AbstractRuntimePreset $preset): DynamicForm
    {
        return DynamicForm::make([]);
    }
}
