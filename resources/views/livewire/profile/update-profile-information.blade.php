@php
    $user = auth()->user();
@endphp
<x-card>
    <x-slot name="title">
        {{ __('Profile Information') }}
    </x-slot>

    <x-slot name="description">
        {{ __("Update your account's profile information and email address.") }}
    </x-slot>

    <form id="send-verification" wire:submit.prevent="sendVerificationEmail">
    </form>

    <form id="update-profile-information" wire:submit.prevent="submit" class="mt-6 space-y-6">
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input wire:model.defer="name" id="name" name="name" type="text" class="mt-1 block w-full" required autocomplete="name" />
            @error('name')
            <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model.defer="email" id="email" name="email" type="email" class="mt-1 block w-full" required autocomplete="username" />
            @error('email')
            <x-input-error class="mt-2" :messages="$message" />
            @enderror

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800 dark:text-gray-200">
                        {{ __('Your email address is unverified.') }}
                        <button form="send-verification" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>
                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600 dark:text-green-400">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div>
            <x-input-label for="timezone" :value="__('Timezone')" />
            <x-select-input wire:model.defer="timezone" id="timezone" name="timezone" class="mt-1 block w-full" required>
                @foreach(timezone_identifiers_list() as $timezone)
                    <option value="{{ $timezone }}">{{ $timezone }}</option>
                @endforeach
            </x-select-input>
            @error('timezone')
            <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>
    </form>

    <x-slot name="actions">
        @if (session('status') === 'profile-updated')
            <p class="mr-2">{{ __('Saved') }}</p>
        @endif
        <x-primary-button form="update-profile-information" wire:loading.attr="disabled">{{ __('Save') }}</x-primary-button>
    </x-slot>
</x-card>
