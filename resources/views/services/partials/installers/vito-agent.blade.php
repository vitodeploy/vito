<x-secondary-button class="!w-full" x-on:click="$dispatch('open-modal', 'install-vito-agent')">
    Install
</x-secondary-button>
@push("modals")
    <x-modal name="install-vito-agent">
        <form
            id="install-vito-agent-form"
            hx-post="{{ route("servers.services.install", ["server" => $server]) }}"
            hx-swap="outerHTML"
            hx-select="#install-vito-agent-form"
            class="p-6"
        >
            @csrf
            <input type="hidden" name="name" value="vito-agent" />
            <input type="hidden" name="type" value="monitoring" />
            <input type="hidden" name="version" value="latest" />

            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __("Install Vito Agent") }}
            </h2>

            @error("type")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror

            <div class="mt-6">
                <x-alert-warning>
                    Vito Agent is only works if you are running your Vito instance on a cloud not local! Consider
                    installing remote-monitor instead.
                </x-alert-warning>
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    {{ __("Cancel") }}
                </x-secondary-button>

                <x-primary-button id="btn-vito-agent" hx-disable class="ml-3">
                    {{ __("Install") }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
@endpush
