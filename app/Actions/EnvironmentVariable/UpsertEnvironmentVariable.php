<?php

namespace App\Actions\EnvironmentVariable;

use App\Actions\Environment\MarkEnvironmentForRedeploy;
use App\Enums\ApplicationStatus;
use App\Enums\EnvironmentStatus;
use App\Models\Application;
use App\Models\Environment;
use App\Models\EnvironmentVariable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UpsertEnvironmentVariable
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function upsert(Environment $environment, array $input, ?EnvironmentVariable $variable = null, bool $markForRedeploy = true): EnvironmentVariable
    {
        return DB::transaction(function () use ($environment, $input, $variable, $markForRedeploy): EnvironmentVariable {
            $application = Application::query()->lockForUpdate()->findOrFail($environment->application_id);
            $environment = Environment::query()->lockForUpdate()->findOrFail($environment->id);
            $variable = $variable ? $environment->variables()->findOrFail($variable->id) : null;
            $this->validate($environment, $input, $variable);

            if ($application->status !== ApplicationStatus::READY || $environment->status !== EnvironmentStatus::READY) {
                throw ValidationException::withMessages(['key' => 'Variables cannot be changed during teardown.']);
            }

            $variable ??= new EnvironmentVariable;
            $variable->environment_id = $environment->id;
            $variable->key = $input['key'];
            if (array_key_exists('value', $input) && $input['value'] !== null) {
                $variable->value = str_replace(["\r\n", "\r"], "\n", $input['value']);
            }
            $variable->is_secret = $input['is_secret'];
            $variable->save();
            if ($markForRedeploy) {
                app(MarkEnvironmentForRedeploy::class)->mark($environment);
            }

            return $variable;
        });
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function validate(Environment $environment, array $input, ?EnvironmentVariable $variable): void
    {
        Validator::make($input, [
            'key' => ['required', 'string', 'max:255', 'regex:/\A[A-Z_][A-Z0-9_]*\z/', 'not_regex:/'.EnvironmentVariable::RESERVED_KEY_PATTERN.'/', Rule::unique('environment_variables', 'key')
                ->where('environment_id', $environment->id)->ignore($variable?->id)],
            'value' => [$variable?->is_secret ? 'sometimes' : 'present', ...($variable?->is_secret ? ['nullable'] : []), 'string', 'not_regex:/\x00/'],
            'is_secret' => ['required', 'boolean'],
        ], [
            'key.regex' => 'Use uppercase letters, digits and underscores, starting with a letter or underscore.',
            'key.not_regex' => 'This key is reserved by the command shell.',
        ])->validate();

        if ($variable?->is_secret && ! $input['is_secret'] && ! isset($input['value'])) {
            throw ValidationException::withMessages(['value' => 'Enter a new value to make a secret public.']);
        }
    }
}
