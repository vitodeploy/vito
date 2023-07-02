<div x-data="">
    <x-item-card>
        <div class="flex-none">
            @include('livewire.source-controls.partials.gitlab-icon')
        </div>
        <div class="ml-3 flex flex-grow flex-col items-start justify-center">
            <span class="mb-1">{{ __("Gitlab") }}</span>
        </div>
        <div class="flex items-center">
            <div class="inline">
                @if($sourceControl)
                    <x-secondary-button x-on:click="$dispatch('open-modal', 'connect-gitlab')">{{ __("Modify") }}</x-secondary-button>
                @else
                    <x-primary-button x-on:click="$dispatch('open-modal', 'connect-gitlab')">{{ __("Connect") }}</x-primary-button>
                @endif
            </div>
        </div>
    </x-item-card>
    <x-modal name="connect-gitlab">
        <form wire:submit.prevent="connect" class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __('Connect to Gitlab') }}
            </h2>

            <div class="mt-6">
                <x-input-label for="token" :value="__('Access Token')" />
                <x-text-input wire:model.defer="token" id="token" name="token" type="text" :value="$sourceControl ? $sourceControl->token : ''" class="mt-1 w-full" />
                @error('token')
                <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6 flex items-center justify-end">
                @if (session('status') === 'gitlab-updated')
                    <p class="mr-2">{{ __('Updated') }}</p>
                @endif

                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-primary-button class="ml-3" @connected.window="$dispatch('close')">
                    {{ __('Connect') }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
