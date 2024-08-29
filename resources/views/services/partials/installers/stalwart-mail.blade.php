<x-secondary-button class="!w-full" x-on:click="$dispatch('open-modal', 'install-stalwart-mail')">
    Install
</x-secondary-button>
@push("modals")
    <x-modal name="install-stalwart-mail">
        <form
            id="install-stalwart-mail-form"
            hx-post="{{ route("servers.services.install", ["server" => $server]) }}"
            hx-swap="outerHTML"
            hx-select="#install-stalwart-mail-form"
            class="p-6"
        >
            @csrf
            <input type="hidden" name="name" value="stalwart-mail" />
            <input type="hidden" name="type" value="email_service" />

            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __("Install Stalwart") }}
            </h2>

            <div class="mt-6">
                <x-input-label for="version" value="Version" />
                <x-select-input id="version" name="version" class="mt-1 w-full">
                    <option value="latest">Latest</option>
                </x-select-input>
                @error("version")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>
            <div class="mt-6">
                <x-input-label for="domain" value="Main Hostname Domain" />
                <x-text-input
                    :required="true"
                    placeholder="mail.example.com"
                    id="domain"
                    name="domain"
                    class="mt-2 w-full"
                />

                @error("domain")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6">
                <x-input-label for="site_domain" value="Stalwart Panel Access Domain" />
                <x-text-input
                    :required="true"
                    placeholder="stalwart.example.com"
                    id="site_domain"
                    name="site_domain"
                    class="mt-2 w-full"
                />
                <span class="text-xs">
                    We will use this domain to create a new site to access Stalwart admin panel instead of using an IP
                    address and port.
                </span>
                @error("site_domain")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6 text-red-600">
                {!!
                    __("After installation you should check <strong><a href=\":logs\">server logs</a></strong> to know the user/password to access Stalwart.", [
                        "logs" => route("servers.logs", $server),
                    ])
                !!}
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    {{ __("Cancel") }}
                </x-secondary-button>

                <x-primary-button id="btn-install-stalwart-mail" hx-disable class="ml-3">
                    {{ __("Install") }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
@endpush
