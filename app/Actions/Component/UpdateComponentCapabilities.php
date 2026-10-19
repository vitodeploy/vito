<?php

namespace App\Actions\Component;

use App\Enums\ApplicationStatus;
use App\Enums\ComponentStatus;
use App\Enums\EnvironmentStatus;
use App\Jobs\Component\SyncComponentCapabilitiesJob;
use App\Models\Application;
use App\Models\Component;
use App\Models\ComponentCapability;
use App\Models\Environment;
use App\Models\Server;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UpdateComponentCapabilities
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function update(Component $component, array $input): Component
    {
        Validator::make($input, [
            'capabilities' => ['present', 'array', 'list'],
            'capabilities.*' => ['array:capability,config'],
            'capabilities.*.capability' => ['required', 'string', 'distinct', Rule::in(array_keys(config('component.capabilities', [])))],
            'capabilities.*.config' => ['sometimes', 'nullable', 'array'],
        ])->validate();

        try {
            $component = Cache::lock('unique-queue:server-'.$component->server_id, 600)->block(10, fn (): Component => DB::transaction(function () use ($component, $input): Component {
                Application::query()->lockForUpdate()->findOrFail($component->environment->application_id);
                Environment::query()->lockForUpdate()->findOrFail($component->environment_id);
                $server = Server::query()->lockForUpdate()->findOrFail($component->server_id);
                $component->refresh();
                app(\App\Actions\EnvironmentDeployment\AssertEnvironmentIdle::class)->validate($component->environment, 'capabilities');
                if ($component->status !== ComponentStatus::READY || ! $server->isReady()
                    || $component->environment->status !== EnvironmentStatus::READY
                    || $component->environment->application->status !== ApplicationStatus::READY) {
                    throw ValidationException::withMessages(['capabilities' => 'Only a ready component outside teardown can change capabilities.']);
                }

                $existing = $component->capabilities()->get()->keyBy('capability');
                foreach ($input['capabilities'] as $configuration) {
                    $capability = $existing->get($configuration['capability']) ?? new ComponentCapability([
                        'component_id' => $component->id, 'capability' => $configuration['capability'],
                    ]);
                    $capability->config = $configuration['config'] ?? [];
                    try {
                        $capability->handler()->validate($component, $server);
                    } catch (ValidationException $e) {
                        throw ValidationException::withMessages(['capabilities' => array_merge(...array_values($e->errors()))]);
                    }
                    if (! $capability->exists || $capability->isDirty('config')) {
                        $capability->status = ComponentStatus::PENDING;
                        $capability->save();
                    }
                    $existing->forget($configuration['capability']);
                }
                foreach ($existing as $capability) {
                    if ($capability->capability === 'web' && $component->domains()->exists()) {
                        throw ValidationException::withMessages(['capabilities' => 'Remove the domains targeting this component before removing web.']);
                    }
                    $capability->status = ComponentStatus::DELETING;
                    $capability->save();
                }
                if ($component->capabilities()->where('status', '!=', ComponentStatus::READY)->exists()) {
                    $component->status = ComponentStatus::PENDING;
                    $component->needs_redeploy = true;
                    $component->save();
                    DB::afterCommit(fn () => app(BroadcastComponentUpdate::class)->broadcast($component));
                }

                return $component;
            }));
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages(['capabilities' => 'Another operation on this server is in progress. Please retry.']);
        }

        if ($component->status === ComponentStatus::PENDING) {
            SyncComponentCapabilitiesJob::dispatch($component->id, $component->server_id)->afterCommit();
        }

        return $component;
    }
}
