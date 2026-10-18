<?php

namespace App\Helpers;

use App\DTOs\ToolingTargetDTO;
use App\Models\Component;
use App\Tooling\ToolingRegistry;

final class ComponentShell
{
    public static function wrap(Component $component, string $command, ?string $envFile = null, ?string $cwd = null): string
    {
        $target = ToolingTargetDTO::forComponent($component);
        $environment = SiteShellEnvironment::collect($target);
        $toolId = $component->environment->application->preset()->tooling();
        $paths = $toolId === null ? [] : (ToolingRegistry::find($toolId)?->pathContributions($target) ?? []);
        $environment['PATH'] = implode(':', [...$paths, $environment['PATH'] ?? "/usr/local/bin:/usr/bin:/bin:/home/{$component->user}/.local/bin"]);

        $script = '';
        foreach ($environment as $key => $value) {
            $script .= 'export '.$key.'='.escapeshellarg($value).'; ';
        }
        if ($cwd === null) {
            $script .= '[ -d '.escapeshellarg($component->currentPath()).' ] || exit 0; ';
        }
        $script .= 'cd -- '.escapeshellarg($cwd ?? $component->currentPath()).' || exit 1; ';
        $script .= 'exec '.escapeshellarg($component->basePath().'/vito-env-exec').' '.escapeshellarg($envFile ?? $component->envPath()).' /bin/bash -p -c '.escapeshellarg($command);

        return 'bash -c '.escapeshellarg('exec bash -c "$(echo '.base64_encode($script).' | base64 -d)"');
    }
}
