<x-card>
    <x-slot name="title">
        {{ __('Update Password') }}
    </x-slot>

    <x-slot name="description">
        {{ __('Ensure your account is using a long, random password to stay secure.') }}
    </x-slot>

    <form id="update-password" wire:submit="update" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <div>
            <x-input-label for="current_password" :value="__('Current Password')" />
            <x-text-input wire:model="current_password" id="current_password" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
            @error('current_password')
            <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>

        <div>
            <x-input-label for="password" :value="__('New Password')" />
            <x-text-input wire:model="password" id="password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            @error('password')
            <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input wire:model="password_confirmation" id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            @error('password_confirmation')
            <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>

    </form>
    <x-slot name="actions">
        @if (session('status') === 'password-updated')
            <p class="mr-2">{{ __('Saved') }}</p>
        @endif
        <x-primary-button form="update-password" wire:loading.attr="disabled">{{ __('Save') }}</x-primary-button>
    </x-slot>
</x-card>
