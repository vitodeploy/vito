<?php

namespace App\Actions\Component;

use App\Actions\EnvironmentDeployment\AssertEnvironmentIdle;
use App\Actions\Network\ResolveServerAddress;
use App\Enums\ApplicationStatus;
use App\Enums\ComponentStatus;
use App\Enums\ComponentType;
use App\Enums\EnvironmentStatus;
use App\Enums\LoadBalancerMethod;
use App\Enums\ServiceStatus;
use App\Models\Application;
use App\Models\Component;
use App\Models\ComponentBackend;
use App\Models\ComponentCapability;
use App\Models\Environment;
use App\Models\Server;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UpdateLoadBalancerBackends
{
    /** @param array<string, mixed> $input */
    public function update(Component $component, array $input): void
    {
        $serverIds = $component->environment->components()->distinct()->orderBy('server_id')->pluck('server_id')->all();
        $operation = fn () => DB::transaction(function () use ($component, $input, $serverIds): void {
            Application::query()->lockForUpdate()->findOrFail($component->environment->application_id);
            Environment::query()->lockForUpdate()->findOrFail($component->environment_id);
            Server::query()->whereKey($serverIds)->orderBy('id')->lockForUpdate()->get();
            $component->refresh();
            if ($component->environment->components()->distinct()->orderBy('server_id')->pluck('server_id')->all() !== $serverIds) {
                throw ValidationException::withMessages(['backends' => 'Components changed. Please retry.']);
            }
            app(AssertEnvironmentIdle::class)->validate($component->environment, 'backends');
            $backends = $this->validate($component, $input);
            $component->backends()->delete();
            $component->load_balancer_method = LoadBalancerMethod::from($input['method']);
            $component->last_error = null;
            $component->save();
            $component->backends()->createMany($backends);
        });
        foreach (array_reverse($serverIds) as $serverId) {
            $next = $operation;
            $operation = fn () => Cache::lock('unique-queue:server-'.$serverId, 600)->block(10, $next);
        }
        try {
            $operation();
            app(SyncLoadBalancerBackends::class)->dispatch($serverIds);
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages(['backends' => 'Another operation on a backend server is in progress. Please retry.']);
        }
    }

    /**
     * @param  array<string, mixed>  $input
     * @return list<array<string, mixed>>
     */
    private function validate(Component $component, array $input): array
    {
        if ($component->type !== ComponentType::LOAD_BALANCER || $component->status !== ComponentStatus::READY
            || ! $component->server->isReady() || $component->environment->status !== EnvironmentStatus::READY
            || $component->environment->application->status !== ApplicationStatus::READY) {
            throw ValidationException::withMessages(['backends' => 'Only a ready load balancer outside teardown can change backends.']);
        }
        $data = Validator::make($input, [
            'method' => ['required', Rule::enum(LoadBalancerMethod::class)],
            'backends' => ['present', 'array', 'list', 'max:100'],
            'backends.*' => ['array:target_component_id,ip,port,weight,backup'],
            'backends.*.target_component_id' => ['nullable', 'integer', 'required_without:backends.*.ip', 'prohibits:backends.*.ip'],
            'backends.*.ip' => ['nullable', 'ip', 'required_without:backends.*.target_component_id', 'prohibits:backends.*.target_component_id'],
            'backends.*.port' => ['required', 'integer', 'between:1,65535'],
            'backends.*.weight' => ['nullable', 'integer', 'between:1,1000'],
            'backends.*.backup' => ['required', 'boolean'],
        ])->validate();
        $targets = $component->environment->components()->where('type', ComponentType::APP)->where('status', ComponentStatus::READY)
            ->whereHas('capabilities', fn ($query) => $query->where('capability', 'web')->where('status', ComponentStatus::READY))
            ->with('server')->get()->keyBy('id');
        $seen = [];
        $ports = [];
        foreach ($data['backends'] as $index => &$backend) {
            $backend += ['target_component_id' => null, 'ip' => null, 'weight' => null];
            if ($component->server->webserver()?->name === 'caddy' && ($backend['backup'] || ($backend['weight'] !== null && (int) $backend['weight'] !== 1))) {
                throw ValidationException::withMessages(["backends.$index.weight" => 'Caddy supports equal-weight primary backends only.']);
            }
            if ($input['method'] === LoadBalancerMethod::IP_HASH->value && $backend['backup']) {
                throw ValidationException::withMessages(["backends.$index.backup" => 'IP hash does not support backup backends.']);
            }
            if ($backend['target_component_id'] !== null) {
                $target = $targets->get($backend['target_component_id']);
                if (! $target || ! $target->server->isReady() || $target->server->project_id !== $component->server->project_id
                    || ! in_array($target->server->webserver()?->name, ['nginx', 'caddy'], true)
                    || $target->server->firewall()?->status !== ServiceStatus::READY) {
                    throw ValidationException::withMessages(["backends.$index.target_component_id" => 'Select a ready web component with a webserver and firewall in this environment.']);
                }
                app(ResolveServerAddress::class)->between($component->server, $target->server);
                $this->port($component, $target, (int) $backend['port'], $index);
                $listener = $target->server_id.':'.$backend['port'];
                if (isset($ports[$listener]) && $ports[$listener] !== $target->id) {
                    throw ValidationException::withMessages(["backends.$index.port" => 'This port is already selected for another component on the server.']);
                }
                $ports[$listener] = $target->id;
            } else {
                $backend['ip'] = inet_ntop(inet_pton($backend['ip']));
            }
            $key = ($backend['target_component_id'] ?? $backend['ip']).':'.$backend['port'];
            if (isset($seen[$key])) {
                throw ValidationException::withMessages(["backends.$index.port" => 'This backend is already selected.']);
            }
            $seen[$key] = true;
        }
        unset($backend);
        if ($data['backends'] !== [] && ! collect($data['backends'])->contains(fn (array $backend): bool => ! $backend['backup'])) {
            throw ValidationException::withMessages(['backends' => 'Select at least one primary backend.']);
        }

        return $data['backends'];
    }

    private function port(Component $component, Component $target, int $port, int $index): void
    {
        $used = ComponentCapability::query()->where('capability', 'web')->whereHas('component', fn ($query) => $query->where('server_id', $target->server_id))
            ->pluck('config')->map(fn (?array $config): int => (int) ($config['port'] ?? 0))
            ->merge($target->server->sites()->whereNotNull('port')->pluck('port'));
        if ($port < 1024 || in_array($port, [2019, 3306, 5432, 6379, (int) $target->server->port], true) || $used->contains($port)
            || ComponentBackend::query()->where('component_id', '!=', $component->id)->where('target_component_id', '!=', $target->id)
                ->whereHas('targetComponent', fn ($query) => $query->where('server_id', $target->server_id))->where('port', $port)->exists()) {
            throw ValidationException::withMessages(["backends.$index.port" => 'Choose an unused backend port between 1024 and 65535.']);
        }
    }
}
