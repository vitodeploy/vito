<div>
    <x-primary-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'add-channel')">
        {{ __('Add new Channel') }}
    </x-primary-button>

    <x-modal name="add-channel">
        <form wire:submit.prevent="add" class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __('Add new Channel') }}
            </h2>

            <div class="mt-6">
                <x-input-label for="provider" value="Provider" />
                <x-select-input wire:model="provider" id="provider" name="provider" class="mt-1 w-full">
                    <option value="" selected disabled>{{ __("Select") }}</option>
                    @foreach(config('core.notification_channels_providers') as $p)
                        @if($p !== 'custom')
                            <option value="{{ $p }}" @if($provider === $p) selected @endif>{{ $p }}</option>
                        @endif
                    @endforeach
                </x-select-input>
                @error('provider')
                <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6">
                <x-input-label for="label" :value="__('Label')" />
                <x-text-input wire:model.defer="label" id="label" name="label" type="text" class="mt-1 w-full" />
                @error('label')
                <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            @if($provider == \App\Enums\NotificationChannel::EMAIL)
                <div class="mt-6">
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input wire:model.defer="email" id="email" name="email" type="text" class="mt-1 w-full" />
                    @error('email')
                    <x-input-error class="mt-2" :messages="$message" />
                    @enderror
                </div>
            @endif

            @if(in_array($provider, [\App\Enums\NotificationChannel::SLACK, \App\Enums\NotificationChannel::DISCORD]))
                <div class="mt-6">
                    <x-input-label for="webhook_url" :value="__('Webhook URL')" />
                    <x-text-input wire:model.defer="webhook_url" id="webhook_url" name="webhook_url" type="text" class="mt-1 w-full" />
                    @error('webhook_url')
                    <x-input-error class="mt-2" :messages="$message" />
                    @enderror
                </div>
            @endif

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-primary-button class="ml-3" @added.window="$dispatch('close')">
                    {{ __('Add') }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
