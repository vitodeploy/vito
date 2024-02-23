<div>
    <x-primary-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'create-project')">
        {{ __('Connect') }}
    </x-primary-button>

    <x-modal name="create-project" :show="$open">
        <form wire:submit="create" class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __('Create Project') }}
            </h2>

            <div class="mt-6">
                <x-input-label for="name" value="Name" />
                <x-text-input wire:model="inputs.name" id="name" name="name" type="text" class="mt-1 w-full" />
                @error('name')
                <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-primary-button class="ml-3" @created.window="$dispatch('close')">
                    {{ __('Create') }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
