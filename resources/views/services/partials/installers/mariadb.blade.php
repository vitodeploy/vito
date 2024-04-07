<x-secondary-button class="!w-full" x-on:click="$dispatch('open-modal', 'install-mariadb')">Install</x-secondary-button>
@push("modals")
    <x-modal name="install-mariadb">
        <form
            id="install-mariadb-form"
            hx-post="{{ route("servers.services.install", ["server" => $server]) }}"
            hx-swap="outerHTML"
            hx-select="#install-mariadb-form"
            class="p-6"
        >
            @csrf
            <input type="hidden" name="name" value="mariadb" />
            <input type="hidden" name="type" value="database" />

            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __("Install mariadb") }}
            </h2>

            <div class="mt-6">
                <x-input-label for="version" value="Version" />
                <x-select-input id="version" name="version" class="mt-1 w-full">
                    @foreach (collect(config("core.databases_name"))->filter(fn ($value) => $value == "mariadb") as $db => $value)
                        <option value="{{ config("core.databases_version")[$db] }}">
                            {{ config("core.databases_name")[$db] }} {{ config("core.databases_version")[$db] }}
                        </option>
                    @endforeach
                </x-select-input>
                @error("version")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror

                @error("type")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    {{ __("Cancel") }}
                </x-secondary-button>

                <x-primary-button id="btn-install-mariadb" hx-disable class="ml-3">
                    {{ __("Install") }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
@endpush
