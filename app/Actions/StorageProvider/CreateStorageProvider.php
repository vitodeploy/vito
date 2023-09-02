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
            'credentials' => [
                'token' => $input['token'],
            ],
        ]);
        if (! $storageProvider->provider()->connect()) {
            throw ValidationException::withMessages([
                'token' => __("Couldn't connect to the provider"),
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
            'token' => [
                'required',
            ],
        ])->validate();
    }
}
