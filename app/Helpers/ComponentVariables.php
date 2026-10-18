<?php

namespace App\Helpers;

use App\Enums\WebMode;
use App\Models\Component;

class ComponentVariables
{
    /**
     * @return array<string, array{value: string, source: string, is_secret: bool}>
     */
    public static function resolve(Component $component): array
    {
        $component->loadMissing('environment.application', 'environment.variables', 'capabilities');
        $variables = static::resources($component);
        foreach ($component->environment->variables as $variable) {
            $variables[$variable->key] = ['value' => $variable->value, 'source' => 'environment', 'is_secret' => $variable->is_secret];
        }

        $web = $component->capabilities->firstWhere('capability', 'web');
        if ($web && $component->environment->application->preset()->webMode() === WebMode::PROXY && isset($web->config['port'])) {
            $variables['PORT'] = ['value' => (string) $web->config['port'], 'source' => 'vito', 'is_secret' => false];
        }

        return $variables;
    }

    /**
     * @return array<string, array{value: string, source: string, is_secret: bool}>
     */
    protected static function resources(Component $component): array
    {
        return [];
    }
}
