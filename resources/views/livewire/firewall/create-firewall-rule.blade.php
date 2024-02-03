<div>
    <x-primary-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'create-rule')">
        {{ __('Create new Rule') }}
    </x-primary-button>

    <x-modal name="create-rule">
        <form wire:submit="create" class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __('Create new Rule') }}
            </h2>

            <div class="mt-6">
                <x-input-label for="type" :value="__('Rule Type')" />
                <x-select-input wire:model="type" id="type" name="type" class="mt-1 w-full">
                    <option value="allow" @if($type === 'allow') selected @endif>{{ __("Allow") }}</option>
                    <option value="deny" @if($type === 'deny') selected @endif>{{ __("Deny") }}</option>
                </x-select-input>
                @error('type')
                <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-3">
                <div>
                    <x-input-label for="protocol" :value="__('Protocol')" />
                    <x-select-input wire:model.live="protocol" id="protocol" name="protocol" class="mt-1 w-full">
                        @foreach(config('core.firewall_protocols_port') as $key => $value)
                            <option value="{{ $key }}" @if($key === $protocol) selected @endif>{{ $key }}</option>
                        @endforeach
                    </x-select-input>
                    @error('protocol')
                    <x-input-error class="mt-2" :messages="$message" />
                    @enderror
                </div>

                <div>
                    <x-input-label for="port" :value="__('Port')" />
                    <x-text-input wire:model="port" id="port" name="port" type="text" class="mt-1 w-full" />
                    @error('port')
                    <x-input-error class="mt-2" :messages="$message" />
                    @enderror
                </div>

                <div>
                    <x-input-label for="source" :value="__('Source')" />
                    <x-text-input wire:model="source" id="source" name="source" type="text" class="mt-1 w-full" />
                    @error('source')
                    <x-input-error class="mt-2" :messages="$message" />
                    @enderror
                </div>

                <div>
                    <x-input-label for="mask" :value="__('Mask')" />
                    <x-text-input wire:model="mask" id="mask" name="mask" type="text" class="mt-1 w-full" />
                    @error('mask')
                    <x-input-error class="mt-2" :messages="$message" />
                    @enderror
                </div>
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
