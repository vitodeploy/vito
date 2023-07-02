<div>
    <x-primary-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'connect-provider')">
        {{ __('Connect') }}
    </x-primary-button>

    <x-modal name="connect-provider" :show="$open">
        <form wire:submit.prevent="connect" class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __('Connect to a Server Provider') }}
            </h2>

            <div class="mt-6">
                <x-input-label for="provider" value="Provider" />
                <x-select-input wire:model="provider" id="provider" name="provider" class="mt-1 w-full">
                    <option value="" selected disabled>{{ __("Select") }}</option>
                    @foreach(config('core.server_providers') as $p)
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
                <x-input-label for="name" value="Name" />
                <x-text-input wire:model.defer="name" id="name" name="name" type="text" class="mt-1 w-full" />
                @error('name')
                <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            @if($provider === 'aws')
                <div class="mt-6">
                    <x-input-label for="key" value="Access Key" />
                    <x-text-input wire:model.defer="key" id="key" name="key" type="text" class="mt-1 w-full" />
                    @error('key')
                    <x-input-error class="mt-2" :messages="$message" />
                    @enderror
                </div>

                <div class="mt-6">
                    <x-input-label for="secret" value="Secret" />
                    <x-text-input wire:model.defer="secret" id="secret" name="secret" type="text" class="mt-1 w-full" />
                    @error('secret')
                    <x-input-error class="mt-2" :messages="$message" />
                    @enderror
                </div>
            @endif

            @if(in_array($provider, ['hetzner', 'digitalocean', 'vultr', 'linode']))
                <div class="mt-6">
                    <x-input-label for="token" value="API Key" />
                    <x-text-input wire:model.defer="token" id="token" name="token" type="text" class="mt-1 w-full" />
                    @error('token')
                    <x-input-error class="mt-2" :messages="$message" />
                    @enderror
                </div>
            @endif

            <div class="mt-6 flex justify-end">
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
