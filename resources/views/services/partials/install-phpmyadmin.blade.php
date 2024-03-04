<x-modal name="install-phpmyadmin">
    <form wire:submit="install" class="p-6">
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __("Install PHPMyAdmin") }}
        </h2>

        <div class="mt-6">
            <x-input-label for="allowed_ip" :value="__('Allowed IP')" />
            <x-text-input wire:model="allowed_ip" id="allowed_ip" name="allowed_ip" class="mt-1 w-full" />
            @error("allowed_ip")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>

        <div class="mt-6">
            <x-input-label for="port" :value="__('Port')" />
            <x-text-input wire:model="port" id="port" name="port" class="mt-1 w-full" />
            @error("port")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>

        <div class="mt-6 flex justify-end">
            <x-secondary-button type="button" x-on:click="$dispatch('close')">
                {{ __("Cancel") }}
            </x-secondary-button>

            <x-primary-button class="ml-3" @started.window="$dispatch('close')">
                {{ __("Install") }}
            </x-primary-button>
        </div>
    </form>
</x-modal>
