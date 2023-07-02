<?php

namespace App\Actions\StorageProvider;

use App\Models\StorageProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User;
use Throwable;

class HandleProviderCallback
{
    public function callback(Request $request, string $provider): string|RedirectResponse
    {
        try {
            $providerId = $request->session()->get('storage_provider_id');
            /** @var StorageProvider $storageProvider */
            $storageProvider = StorageProvider::query()->findOrFail($providerId);
            /** @var User $oauthUser */
            $oauthUser = Socialite::driver($provider)->user();
            $storageProvider->token = $oauthUser->token;
            $storageProvider->refresh_token = $oauthUser->refreshToken;
            $storageProvider->token_expires_at = now()->addSeconds($oauthUser->expiresIn);
            $storageProvider->connected = true;
            $storageProvider->save();
            /** @TODO toast success message */
        } catch (Throwable) {
            /** @TODO toast failed message */
        }

        return redirect()->route('storage-providers');
    }
}
