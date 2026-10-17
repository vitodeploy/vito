<?php

namespace App\Actions\Environment;

use App\Models\Application;
use App\Models\Environment;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ValidateEnvironment
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function validate(Application $application, array $input, ?Environment $environment = null): void
    {
        $versions = $application->preset()->versions();

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255', Rule::unique('environments')->where('application_id', $application->id)->ignore($environment)],
            'branch' => ['required', 'string', 'max:255'],
            'runtime_version' => [Rule::requiredIf($versions !== []), 'nullable', 'string', 'max:255', Rule::in($versions)],
        ])->validate();

        if ($environment === null) {
            Validator::make(['name' => Str::slug($input['name'])], [
                'name' => ['required', 'max:255', Rule::unique('environments', 'slug')->where('application_id', $application->id)],
            ], [
                'name.required' => 'The name must produce a non-empty slug.',
                'name.unique' => 'An environment with the same slug already exists.',
            ])->validate();
        }
    }
}
