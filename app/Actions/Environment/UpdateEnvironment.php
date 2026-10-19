<?php

namespace App\Actions\Environment;

use App\Actions\Component\BroadcastComponentUpdate;
use App\Actions\GitHook\SyncRepositoryGitHook;
use App\Enums\ApplicationStatus;
use App\Enums\ComponentStatus;
use App\Enums\ComponentType;
use App\Enums\EnvironmentStatus;
use App\Enums\ServiceStatus;
use App\Jobs\Component\SyncComponentRuntimeJob;
use App\Models\Server;
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

            Validator::make($input, [
                'auto_deploy' => ['sometimes', 'boolean'],
                'primary_component_id' => ['sometimes', 'nullable', 'integer', Rule::exists('components', 'id')
                    ->where('environment_id', $environment->id)->where('type', ComponentType::APP->value)
                    ->whereNotIn('status', [ComponentStatus::DELETING->value, ComponentStatus::DELETION_FAILED->value])],
            ])->validate();
            if (array_key_exists('primary_component_id', $input)) {
                $input['primary_component_id'] = $input['primary_component_id'] === null ? null : (int) $input['primary_component_id'];
            }

            $runtimeChanged = ($input['runtime_version'] ?? null) !== $environment->runtime_version;
            if ($runtimeChanged || $input['branch'] !== $environment->branch || (array_key_exists('primary_component_id', $input) && $input['primary_component_id'] !== $environment->primary_component_id)) {
                app(\App\Actions\EnvironmentDeployment\AssertEnvironmentIdle::class)->validate($environment, $runtimeChanged ? 'runtime_version' : 'branch');
            }
            if ($runtimeChanged && $environment->components()->where('status', '!=', ComponentStatus::READY)->exists()) {
                throw ValidationException::withMessages(['runtime_version' => 'All components must be ready before changing the runtime version.']);
            }

            $environment->fill([
                'name' => $input['name'],
                'branch' => $input['branch'],
                'runtime_version' => $input['runtime_version'] ?? null,
            ]);
            if (array_key_exists('primary_component_id', $input)) {
                $environment->primary_component_id = $input['primary_component_id'];
            }
            if (array_key_exists('auto_deploy', $input)) {
                if ($input['auto_deploy'] && ! $application->sourceControl) {
                    throw ValidationException::withMessages(['auto_deploy' => 'Select an available source control before enabling automatic deployment.']);
                }
                $environment->auto_deploy = (bool) $input['auto_deploy'];
            }
            $environment->save();

            if ($runtimeChanged) {
                $components = $environment->components()->with('capabilities', 'server')->get();
                Server::query()->whereKey($components->pluck('server_id')->unique())->orderBy('id')->lockForUpdate()->get();
                foreach ($components as $component) {
                    $component->setRelation('environment', $environment);
                    foreach ($application->preset()->requiredServices($environment->runtime_version ?? '') as $type => $version) {
                        if (! $component->server->services()->where('type', $type)->when($version !== null, fn ($query) => $query->where('version', $version))
                            ->whereIn('status', [ServiceStatus::READY, ServiceStatus::STOPPED, ServiceStatus::DISABLED])->exists()) {
                            throw ValidationException::withMessages(['runtime_version' => 'Install the required runtime service on every component server first.']);
                        }
                    }
                    foreach ($component->capabilities as $capability) {
                        $capability->handler()->validate($component, $component->server);
                    }
                    $component->status = ComponentStatus::PENDING;
                    $component->needs_redeploy = true;
                    $component->save();
                    DB::afterCommit(fn () => app(BroadcastComponentUpdate::class)->broadcast($component));
                    SyncComponentRuntimeJob::dispatch($component->id, $component->server_id)->afterCommit();
                }
            }

            if (array_key_exists('auto_deploy', $input) && $application->sourceControl) {
                DB::afterCommit(fn () => app(SyncRepositoryGitHook::class)->sync($application->sourceControl, $application->repository));
            }

            return $environment;
        });
    }
}
