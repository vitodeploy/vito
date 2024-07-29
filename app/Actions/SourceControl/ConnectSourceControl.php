<?php

namespace App\Actions\SourceControl;

use App\Models\SourceControl;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ConnectSourceControl
{
    public function connect(User $user, array $input): void
    {
        $this->validate($input);

        $sourceControl = new SourceControl([
            'provider' => $input['provider'],
            'profile' => $input['name'],
            'url' => Arr::has($input, 'url') ? $input['url'] : null,
            'project_id' => isset($input['global']) && $input['global'] ? null : $user->current_project_id,
        ]);

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
            'provider' => [
                'required',
                Rule::in(config('core.source_control_providers')),
            ],
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
