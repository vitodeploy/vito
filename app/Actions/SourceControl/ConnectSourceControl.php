<?php

namespace App\Actions\SourceControl;

use App\Models\Project;
use App\Models\SourceControl;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ConnectSourceControl
{
    public function connect(User $user, Project $project, array $input): SourceControl
    {
        $sourceControl = new SourceControl([
            'provider' => $input['provider'],
            'profile' => $input['name'],
            'url' => Arr::has($input, 'url') ? $input['url'] : null,
            'project_id' => isset($input['global']) && $input['global'] ? null : $project->id,
        ]);

        $sourceControl->provider_data = $sourceControl->provider()->createData($input);

        if (! $sourceControl->provider()->connect()) {
            throw ValidationException::withMessages([
                'token' => __('Cannot connect to :provider or invalid token!', ['provider' => $sourceControl->provider]
                ),
            ]);
        }

        $sourceControl->save();

        return $sourceControl;
    }

    public static function rules(array $input): array
    {
        $rules = [
            'name' => [
                'required',
            ],
            'provider' => [
                'required',
                Rule::in(config('core.source_control_providers')),
            ],
        ];

        return array_merge($rules, self::providerRules($input));
    }

    /**
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
