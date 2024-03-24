<x-modal name="install-extension">
    <form
        id="install-extension-form"
        hx-post="{{ route("servers.php.install-extension", ["server" => $server]) }}"
        hx-swap="outerHTML"
        hx-select="#install-extension-form"
        class="p-6"
    >
        <input type="hidden" name="version" :value="version" />

        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __("Install Extension") }}
        </h2>

        <div class="mt-6">
            <x-input-label for="extension" value="Name" />
            <x-select-input name="extension" class="mt-1 w-full">
                <option value="" selected>{{ __("Select") }}</option>
                @foreach (config("core.php_extensions") as $extension)
                    <option
                        value="{{ $extension }}"
                        x-bind:disabled="installedExtensions.includes('{{ $extension }}')"
                    >
                        {{ $extension }}
                    </option>
                @endforeach
            </x-select-input>
            @error("extension")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror

            @error("version")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>

        <div class="mt-6 flex items-center justify-end">
            <x-secondary-button type="button" x-on:click="$dispatch('close')">
                {{ __("Cancel") }}
            </x-secondary-button>

            <x-primary-button hx-disable class="ml-3">
                {{ __("Install") }}
            </x-primary-button>
        </div>
    </form>
</x-modal>
