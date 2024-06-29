<?php

namespace App\Actions\StorageProvider;

use App\Models\StorageProvider;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateStorageProvider
{
    /**
     * @throws ValidationException
     */
    public function create(User $user, array $input): void
    {
        $this->validate($user, $input);

        $storageProvider = new StorageProvider([
            'user_id' => $user->id,
            'provider' => $input['provider'],
            'profile' => $input['name'],
            'project_id' => isset($input['global']) && $input['global'] ? null : $user->current_project_id,
        ]);

        $this->validateProvider($input, $storageProvider->provider()->validationRules());

        $storageProvider->credentials = $storageProvider->provider()->credentialData($input);

        try {
            if (! $storageProvider->provider()->connect()) {
                throw ValidationException::withMessages([
                    'provider' => __("Couldn't connect to the provider"),
                ]);
            }
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'provider' => $e->getMessage(),
            ]);
        }

        $storageProvider->save();
    }

    private function validate(User $user, array $input): void
    {
        Validator::make($input, [
            'provider' => [
                'required',
                Rule::in(config('core.storage_providers')),
            ],
            'name' => [
                'required',
                Rule::unique('storage_providers', 'profile')->where('user_id', $user->id),
            ],
        ])->validate();
    }

    private function validateProvider(array $input, array $rules): void
    {
        Validator::make($input, $rules)->validate();
    }
}
