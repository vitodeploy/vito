<div>
    <x-primary-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'create-ssl')">
        {{ __("Create SSL") }}
    </x-primary-button>

    <x-modal name="create-ssl">
        <form wire:submit="create" class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __("Create SSL") }}
            </h2>

            <div class="mt-6">
                <x-input-label for="type" :value="__('SSL Type')" />
                <x-select-input wire:model.live="type" id="type" name="type" class="mt-1 w-full">
                    <option value="" selected disabled>
                        {{ __("Select") }}
                    </option>
                    @foreach (\App\Enums\SslType::getValues() as $t)
                        <option value="{{ $t }}" @if($t === $type) selected @endif>
                            {{ $t }}
                        </option>
                    @endforeach
                </x-select-input>
                @error("type")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            @if ($type === \App\Enums\SslType::CUSTOM)
                <div class="mt-6">
                    <x-input-label for="certificate" :value="__('Certificate')" />
                    <x-textarea
                        wire:model="certificate"
                        id="certificate"
                        name="certificate"
                        type="text"
                        class="mt-1 w-full"
                        rows="5"
                    />
                    @error("certificate")
                        <x-input-error class="mt-2" :messages="$message" />
                    @enderror
                </div>

                <div class="mt-6">
                    <x-input-label for="private" :value="__('Private Key')" />
                    <x-textarea
                        wire:model="private"
                        id="private"
                        name="private"
                        type="text"
                        class="mt-1 w-full"
                        rows="5"
                    />
                    @error("private")
                        <x-input-error class="mt-2" :messages="$message" />
                    @enderror
                </div>
            @endif

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    {{ __("Cancel") }}
                </x-secondary-button>

                <x-primary-button class="ml-3" @created.window="$dispatch('close')">
                    {{ __("Create") }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
