<div>
    <x-primary-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'add-key')"
    >
        {{ __("Add new Key") }}
    </x-primary-button>

    <x-modal name="add-key">
        <form wire:submit="add" class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __("Add new Key") }}
            </h2>

            <div class="mt-6">
                <x-input-label for="name" :value="__('Name')" />
                <x-text-input
                    wire:model="name"
                    id="name"
                    name="name"
                    type="text"
                    class="mt-1 w-full"
                />
                @error("name")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6">
                <x-input-label for="public_key" :value="__('Public Key')" />
                <x-textarea
                    wire:model="public_key"
                    id="public_key"
                    name="public_key"
                    class="mt-1 w-full"
                    rows="5"
                />
                @error("public_key")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button
                    type="button"
                    x-on:click="$dispatch('close')"
                >
                    {{ __("Cancel") }}
                </x-secondary-button>

                <x-primary-button
                    class="ml-3"
                    @added.window="$dispatch('close')"
                >
                    {{ __("Add") }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
