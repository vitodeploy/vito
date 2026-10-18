<?php

namespace App\Actions\Environment;

use App\Enums\ApplicationStatus;
use App\Enums\ComponentStatus;
use App\Enums\ComponentType;
use App\Enums\EnvironmentStatus;
use App\Models\Application;
use App\Models\Environment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UpdateEnvironment
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function update(Environment $environment, array $input): Environment
    {
        return DB::transaction(function () use ($environment, $input): Environment {
            $application = Application::query()->lockForUpdate()->findOrFail($environment->application_id);
            $environment = Environment::query()->lockForUpdate()->findOrFail($environment->id);
            app(ValidateEnvironment::class)->validate($application, $input, $environment);

            if ($application->status !== ApplicationStatus::READY || $environment->status !== EnvironmentStatus::READY) {
                throw ValidationException::withMessages(['name' => 'The environment cannot be edited during teardown.']);
            }

            if (($input['runtime_version'] ?? null) !== $environment->runtime_version && $environment->components()->exists()) {
                throw ValidationException::withMessages(['runtime_version' => 'The runtime version cannot change while the environment has components.']);
            }

            Validator::make($input, [
                'primary_component_id' => ['sometimes', 'nullable', 'integer', Rule::exists('components', 'id')
                    ->where('environment_id', $environment->id)->where('type', ComponentType::APP->value)
                    ->whereNotIn('status', [ComponentStatus::DELETING->value, ComponentStatus::DELETION_FAILED->value])],
            ])->validate();

            $environment->fill([
                'name' => $input['name'],
                'branch' => $input['branch'],
                'runtime_version' => $input['runtime_version'] ?? null,
            ]);
            if (array_key_exists('primary_component_id', $input)) {
                $environment->primary_component_id = $input['primary_component_id'];
            }
            $environment->save();

            return $environment;
        });
    }
}
