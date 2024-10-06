<?php

namespace App\Actions\StorageProvider;

use App\Models\StorageProvider;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateStorageProvider
{
    /**
     * @throws ValidationException
     */
    public function create(User $user, array $input): void
    {
        $storageProvider = new StorageProvider([
            'user_id' => $user->id,
            'provider' => $input['provider'],
            'profile' => $input['name'],
            'project_id' => isset($input['global']) && $input['global'] ? null : $user->current_project_id,
        ]);

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

    public static function rules(array $input): array
    {
        $rules = [
            'provider' => [
                'required',
                Rule::in(config('core.storage_providers')),
            ],
            'name' => [
                'required',
            ],
        ];

        if (isset($input['provider'])) {
            $provider = (new StorageProvider(['provider' => $input['provider']]))->provider();
            $rules = array_merge($rules, $provider->validationRules());
        }

        return $rules;
    }
}
