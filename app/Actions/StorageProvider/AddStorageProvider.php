<?php

namespace App\Actions\StorageProvider;

use App\Models\StorageProvider;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AddStorageProvider
{
    use ValidateProvider;

    /**
     * @throws ValidationException
     */
    public function add(User $user, array $input): mixed
    {
        $this->validate($user, $input);

        $storageProvider = new StorageProvider([
            'user_id' => $user->id,
            'provider' => $input['provider'],
            'label' => $input['label'],
            'connected' => false,
        ]);
        $storageProvider->save();

        return $storageProvider->provider()->connect();
    }
}
