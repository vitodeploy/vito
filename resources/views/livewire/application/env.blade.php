<div x-data="">
    <x-modal name="update-env">
        <form wire:submit="save" class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __('Update .env File') }}
            </h2>

            <div class="mt-6">
                <x-input-label for="env" :value="__('.env')" />
                <x-textarea wire:model="env" rows="10" id="env" name="env" class="mt-1 w-full" />
                @error('env')
                <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6 flex items-center justify-end">
                @if (session('status') === 'updating-env')
                    <p class="mr-2">{{ __('Updating env...') }}</p>
                @endif

                @if (session('status') === 'env-updated')
                    <p class="mr-2">{{ __('Saved') }}</p>
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
