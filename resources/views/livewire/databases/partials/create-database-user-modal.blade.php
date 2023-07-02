<x-modal name="create-database-user">
    <form wire:submit.prevent="create" class="p-6" x-data="{remote: false}">
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Create Database User') }}
        </h2>

        <div class="mt-6">
            <x-input-label for="user-username" :value="__('Username')" />
            <x-text-input wire:model.defer="username" id="user-username" name="username" type="text" class="mt-1 w-full" />
            @error('username')
            <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>

        <div class="mt-6">
            <x-input-label for="user-password" :value="__('Password')" />
            <x-text-input wire:model.defer="password" id="user-password" name="password" type="text" class="mt-1 w-full" />
            @error('password')
            <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>

        <div class="mt-6">
            <label for="user-remote" class="inline-flex items-center">
                <input id="user-remote" wire:model="remote" type="checkbox" x-model="remote" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" name="remote">
                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Enable remote access') }}</span>
            </label>
        </div>

        <div x-show="remote">
            <div class="mt-6">
                <x-input-label for="user-host" :value="__('Host')" />
                <x-text-input wire:model.defer="host" id="user-host" name="host" type="text" class="mt-1 w-full" />
                <x-input-label for="user-host" :value="__('You might also need to open the database port in Firewall')" class="mt-1"/>
                @error('host')
                <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>
        </div>

        <div class="mt-6 flex justify-end">
            <x-secondary-button type="button" x-on:click="$dispatch('close')">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-primary-button class="ml-3" @database-user-created.window="$dispatch('close')">
                {{ __('Create') }}
            </x-primary-button>
        </div>
    </form>
</x-modal>
