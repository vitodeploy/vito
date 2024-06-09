<?php

namespace App\Actions\ApiKey;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class CreateApiKey
{
    /**
     * @throws ValidationException
     */
    public function create(User $user, array $input): ApiKey
    {
        $this->validate($input);

        $apiSecret = Str::random(32);
        $key = new ApiKey([
            'api_version' => 'v1',
            'user_id' => $user->id,
            'description' => $input['description'],
            'secret' => $apiSecret,
            'expires_at' => $input['expires_at'] ?? null,
            'full_permissions' => $input['permission_type'] ?? false,
            'permissions' => $input['permissions'] ?? [],
            'allowed_ips' => null,
        ]);

        $key->save();

        return $key;
    }

    /**
     * @throws ValidationException
     */
    private function validate(array $input): void
    {
        Validator::make($input, [
            'description' => 'required|string|max:255',
            'expires_at' => 'nullable|date|after:today',
            'permission_type' => 'required|boolean',
            'permissions' => 'required_if:permission_type,false|array',
        ])->validate();
    }
}
