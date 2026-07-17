<?php

namespace App\Actions\Desktop;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\TwoFactorAuthenticationProvider;

class VerifyDesktopUnlock
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function verify(User $user, array $input): void
    {
        $validated = Validator::make($input, [
            'password' => ['required', 'string'],
            'code' => ['nullable', 'string'],
        ])->validate();

        if (! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        if ($user->hasEnabledTwoFactorAuthentication()) {
            $this->verifyTwoFactorCode($user, (string) ($validated['code'] ?? ''));
        }
    }

    private function verifyTwoFactorCode(User $user, string $code): void
    {
        if ($code !== '') {
            if (app(TwoFactorAuthenticationProvider::class)->verify(decrypt($user->two_factor_secret), $code)) {
                return;
            }

            foreach ($user->recoveryCodes() as $recoveryCode) {
                if (hash_equals($recoveryCode, $code)) {
                    $user->replaceRecoveryCode($code);

                    return;
                }
            }
        }

        throw ValidationException::withMessages([
            'code' => __('The provided two factor authentication code was invalid.'),
        ]);
    }
}
