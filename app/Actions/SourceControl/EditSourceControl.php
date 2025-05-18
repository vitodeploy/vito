<?php

namespace App\Actions\SourceControl;

use App\Models\Project;
use App\Models\SourceControl;
use Illuminate\Support\Facades\Validator;
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
        Validator::make($input, self::rules())->validate();

        $sourceControl->profile = $input['name'];
        $sourceControl->project_id = isset($input['global']) && $input['global'] ? null : $project->id;

        $sourceControl->save();

        return $sourceControl;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'name' => [
                'required',
            ],
        ];
    }
}
