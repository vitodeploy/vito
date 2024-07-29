<x-card>
    <x-slot name="title">
        {{ __("Two Factor Authentication") }}
    </x-slot>

    <x-slot name="description">
        {{ __("Here you can activate 2FA to secure your account") }}
    </x-slot>

    <div id="two-factor">
        @if (! auth()->user()->two_factor_secret)
            {{-- Enable 2FA --}}
            <form
                hx-post="{{ route("two-factor.enable") }}"
                hx-target="#two-factor"
                hx-select="#two-factor"
                hx-swap="outerHTML"
            >
                @csrf

                <x-primary-button type="submit">
                    {{ __("Enable Two-Factor") }}
                </x-primary-button>
            </form>
        @else
            {{-- Disable 2FA --}}
            <form
                hx-post="{{ route("two-factor.disable") }}"
                hx-target="#two-factor"
                hx-select="#two-factor"
                hx-swap="outerHTML"
            >
                @csrf
                @method("DELETE")

                <x-danger-button type="submit">
                    {{ __("Disable Two-Factor") }}
                </x-danger-button>
            </form>

            @if (session("status") == "two-factor-authentication-enabled")
                <div class="mt-5">
                    {{ __('Two factor authentication is now enabled. Scan the following QR code using your phone\'s authenticator application.') }}
                </div>

                <div class="mt-5">
                    {!! auth()->user()->twoFactorQrCodeSvg() !!}
                </div>

                <div class="mt-5">
                    {{ __("If you are unable to scan the QR code, please use the 2FA secret instead.") }}
                </div>

                <div class="mt-2">
                    <div class="inline-block rounded-md border border-gray-100 p-2 dark:border-gray-700">
                        {{ decrypt(auth()->user()->two_factor_secret) }}
                    </div>
                </div>
            @endif

            {{-- Show 2FA Recovery Codes --}}
            <div class="mt-5">
                {{ __("Store these recovery codes in a secure password manager. They can be used to recover access to your account if your two factor authentication device is lost.") }}
            </div>

            <div class="mt-5 rounded-md border border-gray-100 p-2 dark:border-gray-700">
                @foreach (json_decode(decrypt(auth()->user()->two_factor_recovery_codes), true) as $code)
                    <div class="mt-2">{{ $code }}</div>
                @endforeach
            </div>

            {{-- Regenerate 2FA Recovery Codes --}}
            <form
                class="mt-5"
                hx-post="{{ route("two-factor.recovery-codes") }}"
                hx-target="#two-factor"
                hx-select="#two-factor"
                hx-swap="outerHTML"
            >
                @csrf

                <x-primary-button type="submit">
                    {{ __("Regenerate Recovery Codes") }}
                </x-primary-button>
            </form>
        @endif
    </div>
</x-card>
