<?php

namespace App\Actions\StorageProvider;

use App\Models\StorageProvider;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class EditStorageProvider
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function edit(StorageProvider $storageProvider, array $input): StorageProvider
    {
        $provider = $storageProvider->provider();

        $rules = array_merge(
            ['name' => ['required']],
            $provider->editValidationRules($input),
        );

        Validator::make($input, $rules)->validate();

        [$credentials, $needsReconnect] = $provider->mergeEditData($input);

        if ($needsReconnect) {
            $this->verify($storageProvider, $credentials);
        }

        $storageProvider->profile = $input['name'];
        $storageProvider->project_id = isset($input['global']) && $input['global'] ? null : $storageProvider->user->currentProject?->id;
        $storageProvider->credentials = $credentials;

        $storageProvider->save();

        return $storageProvider;
    }

    /**
     * @param  array<string, mixed>  $credentials
     *
     * @throws ValidationException
     */
    private function verify(StorageProvider $storageProvider, array $credentials): void
    {
        $original = $storageProvider->credentials;
        $storageProvider->credentials = $credentials;

        try {
            $connected = $storageProvider->provider()->connect();
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'provider' => $e->getMessage(),
            ]);
        } finally {
            $storageProvider->credentials = $original;
        }

        if (! $connected) {
            throw ValidationException::withMessages([
                'provider' => __("Couldn't connect to the provider"),
            ]);
        }
    }
}
