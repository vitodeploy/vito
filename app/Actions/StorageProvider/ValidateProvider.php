<?php

namespace App\Actions\StorageProvider;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

trait ValidateProvider
{
    /**
     * @throws ValidationException
     */
    private function validate(User $user, array $input): void
    {
        Validator::make($input, [
            'label' => [
                'required',
                Rule::unique('storage_providers', 'label')->where('user_id', $user->id),
            ],
            'provider' => [
                'required',
                Rule::in(config('core.storage_providers')),
            ],
        ])->validateWithBag('addStorageProvider');
    }
}
