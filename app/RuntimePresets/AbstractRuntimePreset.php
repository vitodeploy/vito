<?php

namespace App\RuntimePresets;

use App\DTOs\DynamicForm;
use App\Enums\WebMode;
use App\Models\Component;
use App\Models\Environment;
use App\Models\EnvironmentAttachment;
use App\Support\RepositoryFiles;
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

    /**
     * Extra tooling installed for every component, keyed by tooling id with the version to install.
     *
     * @return array<string, string>
     */
    public function additionalTooling(): array
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

    /**
     * Optional fields offered only while an environment is being created.
     */
    public function creationForm(): DynamicForm
    {
        return DynamicForm::make([]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function creationRules(): array
    {
        return [];
    }

    /**
     * Variables seeded into a new environment.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, array{value: string, is_secret: bool}>
     */
    public function defaultVariables(Environment $environment, array $input): array
    {
        return [];
    }

    public function suggestsDatabase(): bool
    {
        return false;
    }

    /**
     * Recognizes this runtime in a repository. Presets are asked in reverse registration order, so
     * a more specific preset registered later wins over a generic one.
     *
     * @return ?list<string> Compatible versions, newest first, or null when the repository is not this runtime.
     */
    public function detect(RepositoryFiles $files): ?array
    {
        return null;
    }

    /**
     * Versions satisfying a Composer or npm style constraint, newest first. An empty, unparsable or
     * unsatisfiable constraint returns every version, so detection never removes the user's choices.
     *
     * @return list<string>
     */
    protected function compatibleVersions(?string $constraint): array
    {
        $versions = $this->versions();
        usort($versions, fn (string $a, string $b): int => version_compare($b, $a));
        $alternatives = preg_split('/\s*\|\|?\s*/', trim(preg_replace('/([<>=^~]+)\s+/', '$1', $constraint ?? '') ?? '')) ?: [];
        $compatible = array_filter($versions, function (string $version) use ($alternatives): bool {
            foreach ($alternatives as $alternative) {
                if ($alternative !== '' && $this->satisfies($version, $alternative)) {
                    return true;
                }
            }

            return false;
        });

        return array_values($compatible ?: $versions);
    }

    /**
     * A caret allows changes right of the first non-zero segment, so ^0.2 stays on 0.2 and ^0.0.2 on 0.0.2.
     *
     * @param  list<string>  $version
     * @param  list<string>  $target
     */
    private function withinCaret(array $version, array $target): bool
    {
        foreach ($target as $index => $segment) {
            if (isset($version[$index]) && $version[$index] !== $segment) {
                return false;
            }
            if ($segment !== '0') {
                return true;
            }
        }

        return true;
    }

    private function satisfies(string $version, string $constraint): bool
    {
        foreach (preg_split('/[\s,]+/', $constraint, flags: PREG_SPLIT_NO_EMPTY) ?: [] as $part) {
            if (! preg_match('/\A(\^|~|>=|<=|>|<|=)?v?(\d+(?:\.\d+)*)/', $part, $match)) {
                return false;
            }
            $precision = min(substr_count($version, '.'), substr_count($match[2], '.')) + 1;
            $current = implode('.', array_slice(explode('.', $version), 0, $precision));
            $target = implode('.', array_slice(explode('.', $match[2]), 0, $precision));
            $sameMajor = (int) $version === (int) $match[2];
            $satisfied = match ($match[1]) {
                '^' => $sameMajor && version_compare($current, $target, '>=') && $this->withinCaret(explode('.', $version), explode('.', $match[2])),
                '~' => substr_count($match[2], '.') >= 2 ? version_compare($current, $target, '==') : $sameMajor && version_compare($current, $target, '>='),
                '>=', '<=', '>', '<' => version_compare($current, $target, $match[1]),
                default => version_compare($current, $target, '=='),
            };
            if (! $satisfied) {
                return false;
            }
        }

        return true;
    }
}
