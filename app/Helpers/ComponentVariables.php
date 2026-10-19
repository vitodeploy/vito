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
        if ($component->type !== \App\Enums\ComponentType::APP) {
            return [];
        }
        $component->loadMissing('server', 'environment.attachments.server', 'environment.attachments.attachable');
        $variables = [];
        foreach ($component->environment->attachments as $attachment) {
            $generic = AttachmentVariables::for($attachment, $component);
            if ($generic === []) {
                continue;
            }
            $aliases = $component->environment->application->preset()->attachmentAliases($attachment, $generic);
            foreach ([...$generic, ...$aliases] as $key => $value) {
                $variables[$key] = ['value' => $value, 'source' => 'resource',
                    'is_secret' => array_key_exists($key, $aliases) || str_ends_with($key, '_PASSWORD') || str_ends_with($key, '_URL')];
            }
        }

        return $variables;
    }
}
