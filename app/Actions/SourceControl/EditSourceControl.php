<?php

namespace App\Actions\SourceControl;

use App\Models\SourceControl;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class EditSourceControl
{
    public function edit(SourceControl $sourceControl, User $user, array $input): void
    {
        $this->validate($input);

        $sourceControl->profile = $input['name'];
        $sourceControl->url = $input['url'] ?? null;
        $sourceControl->project_id = isset($input['global']) && $input['global'] ? null : $user->current_project_id;

        $this->validateProvider($sourceControl, $input);

        $sourceControl->provider_data = $sourceControl->provider()->createData($input);

        if (! $sourceControl->provider()->connect()) {
            throw ValidationException::withMessages([
                'token' => __('Cannot connect to :provider or invalid token!', ['provider' => $sourceControl->provider]
                ),
            ]);
        }

        $sourceControl->save();
    }

    /**
     * @throws ValidationException
     */
    private function validate(array $input): void
    {
        $rules = [
            'name' => [
                'required',
            ],
        ];
        Validator::make($input, $rules)->validate();
    }

    /**
     * @throws ValidationException
     */
    private function validateProvider(SourceControl $sourceControl, array $input): void
    {
        Validator::make($input, $sourceControl->provider()->createRules($input))->validate();
    }
}
