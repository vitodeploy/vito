<?php

namespace App\Actions\Component;

use App\Enums\ApplicationStatus;
use App\Enums\ComponentStatus;
use App\Enums\ComponentType;
use App\Enums\EnvironmentStatus;
use App\Enums\ServerStatus;
use App\Enums\ServiceStatus;
use App\Jobs\Component\InstallComponentJob;
use App\Models\Application;
use App\Models\Component;
use App\Models\ComponentCapability;
use App\Models\Environment;
use App\Models\IsolatedUser;
use App\Models\Server;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateComponent
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function create(Environment $environment, array $input): Component
    {
        Validator::make($input, [
            'server_id' => ['required', 'integer', Rule::exists('servers', 'id')->where('project_id', $environment->application->project_id)->where('status', ServerStatus::READY->value)],
            'user' => ['required', 'string', 'regex:/\A[a-z_][a-z0-9_-]*[a-z0-9]\z/', 'min:3', 'max:32'],
        ])->validate();

        try {
            $component = Cache::lock('unique-queue:server-'.$input['server_id'], 600)->block(10, function () use ($environment, $input): Component {
                $server = Server::query()->findOrFail($input['server_id']);

                return $server->isolatedUserLock($input['user'])->block(30, fn (): Component => $this->attach($environment, $input));
            });
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages(['server_id' => 'Another operation on this server or Linux user is in progress. Please retry.']);
        }

        InstallComponentJob::dispatch($component->id, $component->server_id)->afterCommit();

        return $component;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function attach(Environment $environment, array $input): Component
    {
        return DB::transaction(function () use ($environment, $input): Component {
            $application = Application::query()->lockForUpdate()->findOrFail($environment->application_id);
            $environment = Environment::query()->lockForUpdate()->findOrFail($environment->id);
            $server = Server::query()->lockForUpdate()->findOrFail($input['server_id']);
            $environment->setRelation('application', $application);
            $this->validate($environment, $server, $input);

            $isolatedUser = $input['user'] === $server->getSshUser() ? null : IsolatedUser::query()->firstOrCreate([
                'server_id' => $server->id, 'username' => $input['user'],
            ]);
            $component = new Component([
                'environment_id' => $environment->id,
                'server_id' => $server->id,
                'user' => $input['user'],
                'name' => $input['name'],
                'type' => ComponentType::APP,
            ]);
            $component->isolated_user_id = $isolatedUser?->id;
            $component->path = '/home/'.$component->user.'/app-'.$application->id.'-'.$environment->slug;
            $component->status = ComponentStatus::PENDING;
            $component->save();
            $component->setRelation('environment', $environment);

            foreach ($input['capabilities'] ?? [] as $configuration) {
                $capability = new ComponentCapability([
                    'component_id' => $component->id,
                    'capability' => $configuration['capability'],
                    'config' => $configuration['config'] ?? [],
                ]);
                try {
                    $capability->handler()->validate($component, $server);
                } catch (ValidationException $e) {
                    throw ValidationException::withMessages(['capabilities' => array_merge(...array_values($e->errors()))]);
                }
                $capability->status = ComponentStatus::PENDING;
                $capability->save();
            }

            if (! $environment->primary_component_initialized) {
                $environment->primary_component_id = $component->id;
                $environment->primary_component_initialized = true;
                $environment->save();
            }

            return $component;
        });
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function validate(Environment $environment, Server $server, array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255', Rule::unique('components')->where('environment_id', $environment->id)],
            'server_id' => [Rule::exists('servers', 'id')->where('project_id', $environment->application->project_id)->where('status', ServerStatus::READY->value)],
            'type' => ['required', Rule::in([ComponentType::APP->value])],
            'user' => [Rule::notIn(array_diff(config('core.reserved_user_names'), [$server->getSshUser()])),
                Rule::unique('components')->where('environment_id', $environment->id)->where('server_id', $server->id),
            ],
            'capabilities' => ['sometimes', 'array', 'list'],
            'capabilities.*' => ['array:capability,config'],
            'capabilities.*.capability' => ['required', 'string', 'distinct', Rule::in(array_keys(config('component.capabilities', [])))],
            'capabilities.*.config' => ['sometimes', 'nullable', 'array'],
        ])->validate();

        if ($environment->status !== EnvironmentStatus::READY || $environment->application->status !== ApplicationStatus::READY) {
            throw ValidationException::withMessages(['name' => 'Components cannot be added during teardown.']);
        }

        if (! $environment->application->sourceControl) {
            throw ValidationException::withMessages(['name' => 'Connect a source control before adding components.']);
        }

        $preset = $environment->application->preset();
        foreach ($preset->requiredServices($environment->runtime_version ?? '') as $type => $version) {
            if (! $server->services()->where('type', $type)->when($version !== null, fn ($query) => $query->where('version', $version))
                ->whereIn('status', [ServiceStatus::READY, ServiceStatus::STOPPED, ServiceStatus::DISABLED])->exists()) {
                throw ValidationException::withMessages(['server_id' => "The required {$type} service version must be installed on the server."]);
            }
        }

        if (in_array('web', array_column($input['capabilities'] ?? [], 'capability'), true)
            && $environment->components()->where('server_id', $server->id)->whereHas('capabilities', fn ($query) => $query->where('capability', 'web'))->exists()) {
            throw ValidationException::withMessages(['capabilities' => 'Only one web component per environment is allowed on each server.']);
        }
    }
}
