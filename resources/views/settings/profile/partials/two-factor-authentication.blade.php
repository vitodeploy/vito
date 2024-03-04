<x-card>
    <x-slot name="title">
        {{ __("Two Factor Authentication") }}
    </x-slot>

    <x-slot name="description">
        {{ __("Here you can activate 2FA to secure your account") }}
    </x-slot>

    @if (! auth()->user()->two_factor_secret)
        {{-- Enable 2FA --}}
        <form method="POST" action="{{ route("two-factor.enable") }}">
            @csrf

            <x-primary-button type="submit">
                {{ __("Enable Two-Factor") }}
            </x-primary-button>
        </form>
    @else
        {{-- Disable 2FA --}}
        <form method="POST" action="{{ route("two-factor.disable") }}">
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
        <form class="mt-5" method="POST" action="{{ route("two-factor.recovery-codes") }}">
            @csrf

            <x-primary-button type="submit">
                {{ __("Regenerate Recovery Codes") }}
            </x-primary-button>
        </form>
    @endif
</x-card>
