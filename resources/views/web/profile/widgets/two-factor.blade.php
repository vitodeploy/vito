<div>
    <x-filament::section
        heading="Two Factor Authentication"
        description="Here you can activate 2FA to secure your account"
    >
        <div class="space-y-3">
            @if ($this->enabled)
                @if ($this->showCodes)
                    {!! auth()->user()->twoFactorQrCodeSvg() !!}

                    <div>If you are unable to scan the QR code, please use the 2FA secret instead.</div>

                    <div class="inline-block rounded-md border border-gray-100 p-2 dark:border-gray-700">
                        {{ decrypt(auth()->user()->two_factor_secret) }}
                    </div>
                @endif

                <div>
                    Store these recovery codes in a secure password manager. They can be used to recover access to your
                    account if your two factor authentication device is lost.
                </div>

                <div class="mt-5 rounded-lg border border-gray-100 p-2 dark:border-gray-700">
                    @foreach (json_decode(decrypt(auth()->user()->two_factor_recovery_codes), true) as $code)
                        <div class="mt-2">{{ $code }}</div>
                    @endforeach
                </div>
            @endif

            {{ $this->form }}
        </div>
    </x-filament::section>
</div>
