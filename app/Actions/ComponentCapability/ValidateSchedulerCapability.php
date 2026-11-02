<?php

namespace App\Actions\ComponentCapability;

use App\Models\Component;
use App\Models\ComponentCapability;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ValidateSchedulerCapability
{
    /**
     * @throws ValidationException
     */
    public function validate(ComponentCapability $capability, Component $component): void
    {
        $config = array_replace([
            'command' => $component->environment->application->preset()->schedulerCommand(),
        ], $capability->config ?? []);

        Validator::make($config, [
            'command' => ['required', 'string', 'max:65535', 'not_regex:/\x00/'],
        ])->validate();

        if (ComponentCapability::query()
            ->where('capability', 'scheduler')
            ->when($capability->exists, fn ($query) => $query->whereKeyNot($capability->id))
            ->whereHas('component', fn ($query) => $query->where('environment_id', $component->environment_id))
            ->exists()) {
            throw ValidationException::withMessages([
                'capabilities' => __('Only one scheduler is allowed per environment.'),
            ]);
        }

        $capability->config = $config;
    }
}
