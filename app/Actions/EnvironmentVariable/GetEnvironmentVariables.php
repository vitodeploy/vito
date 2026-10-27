<?php

namespace App\Actions\EnvironmentVariable;

use App\Helpers\ComponentVariables;
use App\Models\Environment;

class GetEnvironmentVariables
{
    /**
     * @return list<array{id: ?int, key: string, value: string, is_secret: bool, source: string, component_name: ?string}>
     */
    public function get(Environment $environment): array
    {
        $environment->loadMissing('variables', 'application', 'components.capabilities', 'components.server');
        $variables = [];
        foreach ($environment->variables->sortBy('key') as $variable) {
            $variables[] = [
                'id' => $variable->id,
                'key' => $variable->key,
                'value' => $variable->value,
                'is_secret' => $variable->is_secret,
                'source' => 'environment',
                'component_name' => null,
            ];
        }
        foreach ($environment->components as $component) {
            $component->setRelation('environment', $environment);
            foreach (ComponentVariables::resolve($component) as $key => $variable) {
                if ($variable['source'] !== 'environment') {
                    $variables[] = ['id' => null, 'key' => $key, ...$variable, 'component_name' => $component->name];
                }
            }
        }

        return $variables;
    }
}
