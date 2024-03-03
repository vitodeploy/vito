<div x-data="">
    <x-modal name="deployment-script">
        <form wire:submit="save" class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __("Deployment Script") }}
            </h2>

            <div class="mt-6">
                <x-input-label for="script" :value="__('Script')" />
                <x-textarea
                    wire:model="script"
                    rows="10"
                    id="script"
                    name="script"
                    class="mt-1 w-full"
                />
                @error("script")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6 flex items-center justify-end">
                @if (session("status") === "script-updated")
                    <p class="mr-2">{{ __("Saved") }}</p>
                @endif

                <x-secondary-button
                    type="button"
                    x-on:click="$dispatch('close')"
                >
                    {{ __("Cancel") }}
                </x-secondary-button>

                <x-primary-button class="ml-3">
                    {{ __("Save") }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
