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
     * @throws ValidationException
     */
    public function update(Site $site, array $input): void
    {
        Validator::make($input, [
            'env' => ['nullable', 'string'],
            'variables' => ['nullable', 'array', 'min:1'],
            'variables.*.key' => ['required_with:variables', 'string'],
            'variables.*.value' => ['nullable', 'string'],
            'variables.*.is_secret' => ['nullable', 'boolean'],
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

        $site->server->os()->write(
            $path,
            $hasVariables ? EnvParser::stringify($variables) : trim((string) ($input['env'] ?? null)),
            $site->user,
        );

        $site->env_variables = $this->secretKeys($variables);
        $site->jsonUpdate('type_data', 'env_path', $path, save: false);
        $site->save();
    }

    /**
     * Build the variables to write to the .env file. Values are taken from the
     * incoming request, restoring masked secrets from the live server file so a
     * secret left empty in the form is never wiped.
     *
     * A key already stored as secret cannot be wiped by submitting it as a
     * non-secret with an empty value: while the submitted value is empty it
     * stays secret so its live value is restored rather than blanked. To make a
     * secret key non-secret the user drops it and re-adds it with a fresh value,
     * which is honoured because a real value is supplied.
     *
     * The raw-text path has no per-field secret toggle, so existing secret
     * classifications are carried over and newly introduced keys fall back to
     * pattern auto-detection. No secret restoration is performed on that path,
     * so callers must supply real values.
     *
     * @param  array<string, mixed>  $input
     * @return array<int, array{key: string, value: string, is_secret: bool}>
     *
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

        return array_map(function ($variable) use ($secretKeys) {
            $variable['is_secret'] = $variable['is_secret'] || isset($secretKeys[$variable['key']]);

            return $variable;
        }, EnvParser::parse(trim((string) ($input['env'] ?? null))));
    }

    /**
     * Guard against silently wiping a previously stored secret. Such a secret
     * submitted with an empty value relies on its value being restored from the
     * live server file; if the file could not be read (an SSH failure is
     * swallowed by getEnv() and yields an empty parse) we would write it out
     * blank. Abort instead so the user can retry rather than lose the value.
     *
     * Only keys already stored as secret are guarded — a brand-new key submitted
     * empty has no value to lose, so an empty live file is treated as legitimate.
     *
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
     * Collect the list of keys marked as secret. Only this list is persisted to
     * the database — env values themselves always live on the server.
     *
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
