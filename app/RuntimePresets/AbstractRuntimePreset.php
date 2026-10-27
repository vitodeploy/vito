<?php

namespace App\RuntimePresets;

use App\DTOs\DynamicForm;
use App\Enums\WebMode;
use App\Models\Component;
use App\Models\Environment;
use App\Models\EnvironmentAttachment;
use Illuminate\Support\Facades\File;

abstract class AbstractRuntimePreset
{
    abstract public static function id(): string;

    abstract public static function label(): string;

    abstract public function webMode(): WebMode;

    /** @return array<string, mixed> */
    public function vhostData(Component $component): array
    {
        $web = $component->capabilities->firstWhere('capability', 'web');
        $version = $component->environment->runtime_version;
        $socket = $component->user === $component->server->getSshUser()
            ? '/var/run/php/php'.$version.'-fpm.sock'
            : '/run/php/php'.$version.'-fpm-'.$component->user.'.sock';

        return [
            'root' => rtrim($component->currentPath().'/'.ltrim($web?->config['web_directory'] ?? '', '/'), '/'),
            'is_php' => $this->webMode() === WebMode::FPM,
            'is_component_proxy' => $this->webMode() === WebMode::PROXY,
            'is_static' => $this->webMode() === WebMode::STATIC,
            'php_socket' => $socket,
            'proxy_address' => '127.0.0.1:'.($web?->config['port'] ?? ''),
        ];
    }

    /**
     * @return list<string>
     */
    public function versions(): array
    {
        return [];
    }

    /**
     * @return array<string, ?string>
     */
    public function requiredServices(string $version): array
    {
        return [];
    }

    public function tooling(): ?string
    {
        return null;
    }

    public function sharedTooling(): bool
    {
        return true;
    }

    public function toolingVersion(string $version): string
    {
        return $version;
    }

    /**
     * @return array<string, string>
     */
    public function executables(string $version): array
    {
        return [];
    }

    public function webRoot(): ?string
    {
        return null;
    }

    public function startCommand(): ?string
    {
        return null;
    }

    public function schedulerCommand(): ?string
    {
        return null;
    }

    /**
     * @return list<string>
     */
    public function sharedPaths(): array
    {
        return [];
    }

    /**
     * @return array{build: string, pre-release: string, post-release: string}
     */
    public function defaultScripts(): array
    {
        $scripts = ['build' => '', 'pre-release' => '', 'post-release' => ''];

        foreach ($scripts as $stage => $script) {
            $path = resource_path('deployment-scripts/presets/'.static::id().'/'.$stage.'.sh');
            $scripts[$stage] = File::exists($path) ? File::get($path) : '';
        }

        return $scripts;
    }

    /**
     * @return list<string>
     */
    public function warnings(Environment $environment): array
    {
        return [];
    }

    /**
     * @param  array<string, string>  $generic
     * @return array<string, string>
     */
    public function attachmentAliases(EnvironmentAttachment $attachment, array $generic): array
    {
        return [];
    }

    public function form(): DynamicForm
    {
        return DynamicForm::make([]);
    }
}
