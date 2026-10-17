<?php

namespace App\Actions\ComponentCapability;

use App\Enums\ServiceStatus;
use App\Enums\WebMode;
use App\Models\Component;
use App\Models\ComponentCapability;
use App\Models\Server;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ValidateWebCapability
{
    /**
     * @throws ValidationException
     */
    public function validate(ComponentCapability $capability, Component $component, Server $server): void
    {
        $preset = $component->environment->application->preset();
        $proxy = $preset->webMode() === WebMode::PROXY;
        $config = array_replace(['web_directory' => $preset->webRoot()], $capability->config ?? []);

        if ($proxy) {
            $config += ['start_command' => $preset->startCommand(), 'port' => null];
            if ($config['port'] === '') {
                $config['port'] = null;
            }
        }

        Validator::make($config, [
            'web_directory' => ['nullable', 'string', 'max:255', 'regex:/\A(?!\/)(?!.*\.\.)[a-zA-Z0-9_.\/-]+\z/'],
            'start_command' => $proxy ? ['required', 'string', 'max:65535', 'not_regex:/\x00/'] : ['prohibited'],
            'port' => $proxy ? ['nullable', 'integer', 'between:1024,65535'] : ['prohibited'],
        ])->validate();

        if ($preset->webMode() === WebMode::FPM) {
            $version = $component->environment->runtime_version;
            if (! $version || ! $server->services()->where('type', 'php')->where('version', $version)->whereIn('status', [ServiceStatus::READY, ServiceStatus::STOPPED, ServiceStatus::DISABLED])->exists()) {
                throw ValidationException::withMessages([
                    'runtime_version' => __('The environment PHP version must be installed on the server.'),
                ]);
            }
        }

        if ($proxy) {
            $config['port'] = $this->port($capability, $server, $config['port'] === null ? null : (int) $config['port']);
        }

        $capability->config = $config;
    }

    private function port(ComponentCapability $capability, Server $server, ?int $requested): int
    {
        $used = ComponentCapability::query()
            ->where('capability', 'web')
            ->when($capability->exists, fn ($query) => $query->whereKeyNot($capability->id))
            ->whereHas('component', fn ($query) => $query->where('server_id', $server->id))
            ->pluck('config')
            ->map(fn (?array $config): int => (int) ($config['port'] ?? 0))
            ->merge($server->sites()->whereNotNull('port')->pluck('port'))
            ->map(fn ($port): int => (int) $port)
            ->all();

        if ($requested !== null) {
            if (in_array($requested, $used, true)) {
                throw ValidationException::withMessages([
                    'port' => __('The port is already used by another component or Site on this server.'),
                ]);
            }

            return $requested;
        }

        $min = max(1024, (int) config('component.port_range.min'));
        $max = min(65535, (int) config('component.port_range.max'));

        for ($port = $min; $port <= $max; $port++) {
            if (! in_array($port, $used, true)) {
                return $port;
            }
        }

        throw ValidationException::withMessages([
            'port' => __('No free application port is available on this server.'),
        ]);
    }
}
