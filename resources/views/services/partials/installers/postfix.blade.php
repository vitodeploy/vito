<x-secondary-button class="!w-full" x-on:click="$dispatch('open-modal', 'install-postfix')">Install</x-secondary-button>
@push("modals")
    <x-modal name="install-postfix">
        <form
            id="install-postfix-form"
            hx-post="{{ route("servers.services.install", ["server" => $server]) }}"
            hx-swap="outerHTML"
            hx-select="#install-postfix-form"
            class="p-6"
        >
            @csrf
            <input type="hidden" name="name" value="postfix" />
            <input type="hidden" name="type" value="email_service" />

            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __("Install Postfix") }}
            </h2>

            <div class="mt-6">
                <x-input-label for="version" value="Version" />
                <x-select-input id="version" name="version" class="mt-1 w-full">
                        <option value="latest">
                            Latest
                        </option>
                </x-select-input>
                @error("version")
                <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>
            <div class="mt-6">
                <x-input-label for="domain" value="Domain" />
                <x-text-input :required="true" placeholder="mail.example.com" id="domain" name="domain" class="mt-2 w-full" />

                @error("domain")
                <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    {{ __("Cancel") }}
                </x-secondary-button>

                <x-primary-button id="btn-install-postfix" hx-disable class="ml-3">
                    {{ __("Install") }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
@endpush
