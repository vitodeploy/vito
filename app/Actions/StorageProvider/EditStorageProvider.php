<?php

namespace App\Actions\StorageProvider;

use App\Models\StorageProvider;
use App\StorageProviders\StorageProvider as StorageProviderContract;
use Illuminate\Support\Facades\Log;
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
    public function edit(StorageProvider $storageProvider, array $input, ?int $projectId): StorageProvider
    {
        if (! $storageProvider->hasProviderHandler()) {
            throw ValidationException::withMessages([
                'provider' => __('This storage provider is no longer available.'),
            ]);
        }

        $provider = $storageProvider->provider();

        $rules = array_merge(
            ['name' => ['required']],
            $provider->editValidationRules($input),
        );

        Validator::make($input, $rules)->validate();

        [$credentials, $needsReconnect] = $provider->mergeEditData($input);

        if ($needsReconnect) {
            $this->verify($storageProvider, $provider, $credentials);
        }

        $storageProvider->profile = $input['name'];
        $storageProvider->project_id = $projectId;

        if ($credentials !== $storageProvider->credentials) {
            $storageProvider->credentials = $credentials;
        }

        $storageProvider->save();

        $provider->forgetCachedState();

        return $storageProvider;
    }

    /**
     * @param  array<string, mixed>  $credentials
     *
     * @throws ValidationException
     */
    private function verify(StorageProvider $storageProvider, StorageProviderContract $provider, array $credentials): void
    {
        try {
            $connected = $provider->connect($credentials);
        } catch (Throwable $e) {
            Log::error('Failed to verify storage provider credentials', [
                'storage_provider_id' => $storageProvider->id,
                'provider' => $storageProvider->provider,
                'exception' => get_class($e),
            ]);

            $connected = false;
        }

        if (! $connected) {
            throw ValidationException::withMessages([
                'provider' => __("Couldn't connect to the provider"),
            ]);
        }
    }
}
