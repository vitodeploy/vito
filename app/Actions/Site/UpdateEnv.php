<?php

namespace App\Actions\Site;

use App\Exceptions\SSHError;
use App\Helpers\EnvParser;
use App\Models\Site;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpdateEnv
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws SSHError
     */
    public function update(Site $site, array $input): void
    {
        Validator::make($input, [
            'env' => ['required_without:variables', 'nullable', 'string'],
            'variables' => ['required_without:env', 'nullable', 'array'],
            'variables.*.key' => ['required_with:variables', 'string'],
            'variables.*.value' => ['nullable', 'string'],
            'variables.*.is_secret' => ['nullable', 'boolean'],
            'path' => ['nullable', 'string', 'regex:/^[a-zA-Z0-9\/_.\-]+$/'],
        ])->validate();

        $typeData = $site->type_data ?? [];
        $path = $input['path'] ?? data_get($typeData, 'env_path', $site->path.'/.env');

        $storedEnvPath = data_get($typeData, 'env_path');
        $withinSitePath = str_starts_with($path, $site->path.'/');
        $matchesStoredPath = $storedEnvPath !== null && $path === $storedEnvPath;

        if (str_contains($path, '..') || (! $withinSitePath && ! $matchesStoredPath)) {
            throw ValidationException::withMessages([
                'path' => __('The path must be within the site directory.'),
            ]);
        }

        $variables = $this->processVariables($site, $input);

        $envContent = EnvParser::stringify($variables);

        $site->server->os()->write(
            $path,
            $envContent,
            $site->user,
        );

        $site->env_variables = $variables;
        $site->save();

        $site->jsonUpdate('type_data', 'env_path', $path);
    }

    /**
     * Process incoming variables, merging with stored secrets
     *
     * @param  array<string, mixed>  $input
     * @return array<int, array{key: string, value: string, is_secret: bool}>
     */
    private function processVariables(Site $site, array $input): array
    {
        if (isset($input['variables']) && is_array($input['variables'])) {
            $incoming = array_map(function ($var) {
                return [
                    'key' => $var['key'] ?? '',
                    'value' => $var['value'] ?? '',
                    'is_secret' => (bool) ($var['is_secret'] ?? false),
                ];
            }, $input['variables']);

            return EnvParser::mergeWithStored($incoming, $site->env_variables);
        }

        $parsed = EnvParser::parse(trim((string) $input['env']));

        if ($site->env_variables) {
            $storedMap = [];
            foreach ($site->env_variables as $var) {
                $storedMap[$var['key']] = $var;
            }

            return array_map(function ($var) use ($storedMap) {
                if (isset($storedMap[$var['key']])) {
                    $var['is_secret'] = $storedMap[$var['key']]['is_secret'];
                }

                return $var;
            }, $parsed);
        }

        return $parsed;
    }
}
