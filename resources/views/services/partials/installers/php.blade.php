<x-secondary-button class="!w-full" x-on:click="$dispatch('open-modal', 'install-php')">Install</x-secondary-button>
@push("modals")
    <x-modal name="install-php">
        <form
            id="install-php-form"
            hx-post="{{ route("servers.services.install", ["server" => $server]) }}"
            hx-swap="outerHTML"
            hx-select="#install-php-form"
            class="p-6"
        >
            @csrf
            <input type="hidden" name="type" value="php" />

            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __("Install PHP") }}
            </h2>

            <div class="mt-6">
                <x-input-label for="version" value="Version" />
                <x-select-input id="version" name="version" class="mt-1 w-full">
                    @foreach (config("core.php_versions") as $p)
                        <option value="{{ $p }}">
                            {{ $p }}
                        </option>
                    @endforeach
                </x-select-input>
                @error("version")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    {{ __("Cancel") }}
                </x-secondary-button>

                <x-primary-button id="btn-install-php" hx-disable class="ml-3">
                    {{ __("Install") }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
@endpush
