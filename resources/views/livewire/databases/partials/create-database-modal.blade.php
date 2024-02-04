<x-modal name="create-database">
    <form wire:submit="create" class="p-6" x-data="{user: false, remote: false}">
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Create Database') }}
        </h2>

        <div class="mt-6">
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input wire:model="name" id="name" name="name" type="text" class="mt-1 w-full" />
            @error('name')
            <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>

        <div class="mt-6">
            <label for="create_user" class="inline-flex items-center">
                <input id="create_user" wire:model="user" type="checkbox" x-model="user" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" name="create_user">
                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Create a user for this database') }}</span>
            </label>
        </div>

        <div x-show="user">
            <div class="mt-6">
                <x-input-label for="db-username" :value="__('Username')" />
                <x-text-input wire:model="username" id="db-username" name="username" type="text" class="mt-1 w-full" />
                @error('username')
                <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6">
                <x-input-label for="db-password" :value="__('Password')" />
                <x-text-input wire:model="password" id="db-password" name="password" type="text" class="mt-1 w-full" />
                @error('password')
                <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6">
                <label for="db-remote" class="inline-flex items-center">
                    <input id="db-remote" wire:model.live="remote" type="checkbox" x-model="remote" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" name="remote">
                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Enable remote access') }}</span>
                </label>
            </div>

            <div x-show="remote">
                <div class="mt-6">
                    <x-input-label for="db-host" :value="__('Host')" />
                    <x-text-input wire:model="host" id="db-host" name="host" type="text" class="mt-1 w-full" />
                    <x-input-label for="db-host" :value="__('You might also need to open the database port in Firewall')" class="mt-1"/>
                    @error('host')
                    <x-input-error class="mt-2" :messages="$message" />
                    @enderror
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-end">
            <x-secondary-button type="button" x-on:click="$dispatch('close')">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-primary-button class="ml-3" @database-created.window="$dispatch('close')">
                {{ __('Create') }}
            </x-primary-button>
        </div>
    </form>
</x-modal>
