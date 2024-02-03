<x-modal name="install-extension">
    <form wire:submit="installExtension" class="p-6">
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Install Extension') }}
        </h2>

        <div class="mt-6">
            <x-input-label for="extension" value="Name" />
            <x-select-input wire:model="extension" name="extension" class="mt-1 w-full">
                <option value="" selected>{{ __("Select") }}</option>
                @foreach(config('core.php_extensions') as $extension)
                    <option value="{{ $extension }}" @if(in_array($extension, $installedExtensions)) disabled @endif>
                        {{ $extension }} @if(in_array($extension, $installedExtensions)) ({{ __("Installed") }}) @endif
                    </option>
                @endforeach
            </x-select-input>
            @error('name')
            <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>

        <div class="mt-6 flex items-center justify-end">
            @if (session('status') === 'started-installation')
                <p class="mr-2">{{ __('Installation Started!') }}</p>
            @endif

            <x-secondary-button type="button" x-on:click="$dispatch('close')">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-primary-button class="ml-3">
                {{ __('Install') }}
            </x-primary-button>
        </div>
    </form>
</x-modal>
