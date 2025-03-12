<?php

namespace App\Actions\SourceControl;

use App\Models\Project;
use App\Models\SourceControl;
use Illuminate\Validation\ValidationException;

class EditSourceControl
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function edit(SourceControl $sourceControl, Project $project, array $input): SourceControl
    {
        $sourceControl->profile = $input['name'];
        $sourceControl->url = $input['url'] ?? null;
        $sourceControl->project_id = isset($input['global']) && $input['global'] ? null : $project->id;

        $sourceControl->provider_data = $sourceControl->provider()->editData($input);

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
    public static function rules(SourceControl $sourceControl, array $input): array
    {
        $rules = [
            'name' => [
                'required',
            ],
        ];

        return array_merge($rules, self::providerRules($sourceControl, $input));
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, array<int, mixed>>
     *
     * @throws ValidationException
     */
    private static function providerRules(SourceControl $sourceControl, array $input): array
    {
        return $sourceControl->provider()->editRules($input);
    }
}
