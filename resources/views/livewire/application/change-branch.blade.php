<div x-data="">
    <x-modal name="change-branch">
        <form wire:submit.prevent="change" class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __('Change Branch') }}
            </h2>

            <div class="mt-6">
                <x-input-label for="branch" :value="__('Branch')" />
                <x-text-input wire:model.defer="branch" id="branch" name="branch" type="text" class="mt-1 w-full" />
                @error('branch')
                <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6 flex items-center justify-end">
                @if (session('status') === 'updating-branch')
                    <p class="mr-2">{{ __('Updating branch...') }}</p>
                @endif

                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-primary-button class="ml-3">
                    {{ __('Save') }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
