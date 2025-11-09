<?php

namespace App\Actions\SourceControl;

use App\Models\SourceControl;
use App\Models\User;
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
    public function connect(User $user, array $input): SourceControl
    {
        $this->validate($input);

        $sourceControl = new SourceControl([
            'provider' => $input['provider'],
            'profile' => $input['name'],
            'url' => isset($input['url']) && $input['url'] ? $input['url'] : null,
            'project_id' => isset($input['global']) && $input['global'] ? null : $user->currentProject?->id,
            'user_id' => $user->id,
        ]);

        $sourceControl->provider_data = $sourceControl->provider()->createData($input);

        try {
            if (! $sourceControl->provider()->connect()) {
                throw ValidationException::withMessages([
                    'provider' => __('Cannot connect to :provider or invalid credentials!', ['provider' => $sourceControl->provider]),
                ]);
            }
        } catch (\Exception $e) {
            // Re-throw validation exceptions as-is
            if ($e instanceof ValidationException) {
                throw $e;
            }

            // For all other exceptions, wrap in validation exception to show the error message in the frontend
            throw ValidationException::withMessages([
                'provider' => $e->getMessage(),
            ]);
        }

        $sourceControl->save();

        return $sourceControl;
    }

    private function validate(array $input): void
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

        Validator::make($input, array_merge($rules, $this->providerRules($input)))->validate();
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, array<string>>
     *
     * @throws ValidationException
     */
    private function providerRules(array $input): array
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
