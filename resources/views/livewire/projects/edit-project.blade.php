<div>
    <x-icon-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'edit-project-{{ $project->id }}')">
        {{ __('Edit') }}
    </x-icon-button>

    <x-modal name="edit-project-{{ $project->id }}">
        <form wire:submit.prevent="save" class="p-6 text-left">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __('Edit Project') }}
            </h2>

            <div class="mt-6">
                <x-input-label for="edit-name-{{ $project->id }}" value="Name" />
                <x-text-input wire:model.defer="inputs.name" id="edit-name-{{ $project->id }}" name="name" type="text" class="mt-1 w-full" />
                @error('name')
                <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-primary-button class="ml-3" @created.window="$dispatch('close')">
                    {{ __('Save') }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
