<?php

namespace App\Actions\SourceControl;

use App\Models\Project;
use App\Models\SourceControl;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ConnectSourceControl
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function connect(Project $project, array $input): SourceControl
    {
        Validator::make($input, self::rules($input))->validate();

        $sourceControl = new SourceControl([
            'provider' => $input['provider'],
            'profile' => $input['name'],
            'url' => isset($input['url']) && $input['url'] ? $input['url'] : null,
            'project_id' => isset($input['global']) && $input['global'] ? null : $project->id,
        ]);

        $sourceControl->provider_data = $sourceControl->provider()->createData($input);

        if (! $sourceControl->provider()->connect()) {
            throw ValidationException::withMessages([
                'token' => __('Cannot connect to :provider or invalid token!', ['provider' => $sourceControl->provider]),
            ]);
        }

        $sourceControl->save();

        return $sourceControl;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, array<int, mixed>>
     */
    public static function rules(array $input): array
    {
        $rules = [
            'name' => [
                'required',
            ],
            'provider' => [
                'required',
                Rule::in(array_keys(config('source-control.providers'))),
            ],
        ];

        return array_merge($rules, self::providerRules($input));
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, array<string>>
     *
     * @throws ValidationException
     */
    private static function providerRules(array $input): array
    {
        if (! isset($input['provider'])) {
            return [];
        }

        $sourceControl = new SourceControl([
            'provider' => $input['provider'],
        ]);

        return $sourceControl->provider()->createRules($input);
    }
}
