<?php

namespace App\Actions\SourceControl;

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
    public function edit(SourceControl $sourceControl, array $input): SourceControl
    {
        Validator::make($input, array_merge(
            ['name' => ['required']],
            $sourceControl->provider()->editRules($input),
        ))->validate();

        $sourceControl->profile = $input['name'];
        $sourceControl->project_id = isset($input['global']) && $input['global']
            ? null
            : $sourceControl->user->currentProject?->id;
        $sourceControl->provider_data = $sourceControl->provider()->editData($input);

        $sourceControl->save();

        return $sourceControl;
    }
}
