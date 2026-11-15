<?php

namespace App\Actions\Site;

use App\Exceptions\SSHError;
use App\Helpers\EnvParser;
use App\Models\Site;
use App\SSH\OS\OS;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpdateEnv
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws SSHError
     * @throws ValidationException
     */
    public function update(Site $site, array $input): void
    {
        Validator::make($input, [
            'env' => ['nullable', 'string', 'max:'.OS::READ_LIMIT],
            'variables' => ['nullable', 'array', 'min:1', 'max:1000'],
            'variables.*.key' => ['required_with:variables', 'string', 'max:255', EnvParser::KEY_RULE],
            'variables.*.value' => ['nullable', 'string', 'max:'.OS::READ_LIMIT],
            'variables.*.is_secret' => ['nullable', 'boolean'],
            'secret_keys' => ['nullable', 'array', 'max:1000'],
            'secret_keys.*' => ['string', 'max:255'],
            'path' => ['nullable', 'string'],
        ])->validate();

        $hasVariables = array_key_exists('variables', $input)
            && is_array($input['variables'])
            && $input['variables'] !== [];

        $hasEnv = array_key_exists('env', $input)
            && ($input['env'] !== null || ! array_key_exists('variables', $input));

        if (! $hasEnv && ! $hasVariables) {
            throw ValidationException::withMessages([
                'env' => __('Either raw content or variables must be provided.'),
            ]);
        }

        if ($hasEnv && $hasVariables) {
            throw ValidationException::withMessages([
                'env' => __('Provide either raw content or variables, not both.'),
            ]);
        }

        $path = $site->resolveEnvPath($input['path'] ?? null);

        $variables = $this->resolveVariables($site, $input, $path, $hasVariables);

        $content = $hasVariables ? EnvParser::stringify($variables) : trim((string) ($input['env'] ?? null));
        if (strlen($content) > OS::READ_LIMIT) {
            throw ValidationException::withMessages([
                'env' => __('The .env file can be at most :bytes bytes.', ['bytes' => OS::READ_LIMIT]),
            ]);
        }

        $site->server->os()->write($path, $content, $site->user);

        $site->env_variables = $this->secretKeys($variables);
        $site->jsonUpdate('type_data', 'env_path', $path, save: false);
        $site->save();
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<int, array{key: string, value: string, is_secret: bool}>
     * @throws SSHError
     * @throws ValidationException
     */
    private function resolveVariables(Site $site, array $input, string $path, bool $hasVariables): array
    {
        $secretKeys = array_flip(EnvParser::secretKeys($site->env_variables));

        if ($hasVariables) {
            $live = EnvParser::parse($site->getEnv($path));

            $this->guardAgainstWipingSecrets($input['variables'], $live, $secretKeys);

            $incoming = array_map(function ($var) use ($secretKeys): array {
                $key = $var['key'] ?? '';
                $value = $var['value'] ?? '';
                $isSecret = (bool) ($var['is_secret'] ?? false);

                if ($value === '' && isset($secretKeys[$key])) {
                    $isSecret = true;
                }

                return [
                    'key' => $key,
                    'value' => $value,
                    'is_secret' => $isSecret,
                ];
            }, $input['variables']);

            return EnvParser::mergeWithLive($incoming, $live);
        }

        $secretKeys += array_flip($input['secret_keys'] ?? []);

        return array_map(function ($variable) use ($secretKeys) {
            $variable['is_secret'] = $variable['is_secret'] || isset($secretKeys[$variable['key']]);

            return $variable;
        }, EnvParser::parse(trim((string) ($input['env'] ?? null))));
    }

    /**
     * @param  array<int, array<string, mixed>>  $incoming
     * @param  array<int, array{key: string, value: string, is_secret: bool}>  $live
     * @param  array<string, int>  $secretKeys
     */
    private function guardAgainstWipingSecrets(array $incoming, array $live, array $secretKeys): void
    {
        if ($live !== []) {
            return;
        }

        foreach ($incoming as $var) {
            $key = $var['key'] ?? '';
            $isEmpty = ($var['value'] ?? '') === '';

            if ($isEmpty && isset($secretKeys[$key])) {
                throw ValidationException::withMessages([
                    'variables' => __('Could not read the current .env file from the server to preserve secret values. Please try again.'),
                ]);
            }
        }
    }

    /**
     * @param  array<int, array{key: string, value: string, is_secret: bool}>  $variables
     * @return array<int, string>
     */
    private function secretKeys(array $variables): array
    {
        $keys = [];

        foreach ($variables as $variable) {
            if ($variable['is_secret'] && $variable['key'] !== '') {
                $keys[] = $variable['key'];
            }
        }

        return array_values(array_unique($keys));
    }
}
