<x-modal name="update-php-ini">
    <form wire:submit="saveIni" class="p-6">
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Update php.ini') }}
        </h2>

        <div class="mt-6">
            <x-input-label for="ini" value="php.ini" />
            <x-textarea wire:model="ini" id="ini" name="ini" class="mt-1 w-full" rows="15" />
            @error('ini')
            <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>

        <div class="mt-6 flex justify-end">
            <x-secondary-button type="button" x-on:click="$dispatch('close')">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-primary-button class="ml-3">
                {{ __('Save') }}
            </x-primary-button>
        </div>
    </form>
</x-modal>
