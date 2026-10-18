<?php

namespace App\Actions\Component;

use App\Enums\ApplicationStatus;
use App\Enums\ComponentStatus;
use App\Enums\EnvironmentStatus;
use App\Models\Application;
use App\Models\Component;
use App\Models\Environment;
use App\Models\ComponentCapability;
use App\Models\CronJob;
use App\Models\Server;
use App\Models\Worker;
use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ValidateComponentProcess
{
    /**
     * @template T
     * @param  Closure(): T  $callback
     * @return T
     */
    public function run(?Component $component, Closure $callback): mixed
    {
        if ($component === null) {
            return $callback();
        }

        try {
            return Cache::lock('unique-queue:server-'.$component->server_id, 600)->block(0, fn () => DB::transaction(function () use ($component, $callback) {
                Application::query()->lockForUpdate()->findOrFail($component->environment->application_id);
                Environment::query()->lockForUpdate()->findOrFail($component->environment_id);
                Server::query()->lockForUpdate()->findOrFail($component->server_id);
                $component->refresh();

                return $callback();
            }));
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages(['name' => 'Another operation on this server is in progress. Please retry.']);
        }
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function validate(Server $server, array $input, ?Component $component, string $capability, Worker|CronJob|null $process = null, ?ComponentCapability $managedBy = null): void
    {
        Validator::make($input, [
            'component_id' => ['prohibited'],
            'component_capability_id' => ['prohibited'],
        ])->validate();

        if ($process?->component_capability_id !== null && $managedBy === null) {
            throw ValidationException::withMessages(['name' => 'This process is managed by its component capability. Edit the capability instead.']);
        }

        if ($managedBy !== null && ($component === null || $managedBy->component_id !== $component->id)) {
            throw ValidationException::withMessages(['name' => 'A managed process must belong to its capability component.']);
        }

        if ($component === null) {
            return;
        }

        if ($component->server_id !== $server->id || $component->project()->id !== $server->project_id
            || ($process && $process->component_id !== $component->id)) {
            throw ValidationException::withMessages(['name' => 'The process must belong to this component and server.']);
        }

        if ($managedBy === null && ($component->status !== ComponentStatus::READY
            || $component->environment->status !== EnvironmentStatus::READY
            || $component->environment->application->status !== ApplicationStatus::READY
            || ($process === null && ! $component->hasCapability($capability)))) {
            throw ValidationException::withMessages(['name' => 'The component and its capability must be ready before managing processes.']);
        }

        Validator::make($input, [
            'user' => ['sometimes', Rule::in([$component->user])],
            'site_id' => ['prohibited'],
            'environment' => ['prohibited'],
            'directory' => ['prohibited'],
        ])->validate();
    }
}
