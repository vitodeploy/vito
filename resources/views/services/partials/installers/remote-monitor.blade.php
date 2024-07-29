<x-secondary-button class="!w-full" x-on:click="$dispatch('open-modal', 'install-remote-monitor')">
    Install
</x-secondary-button>
@push("modals")
    <x-modal name="install-remote-monitor">
        <form
            id="install-remote-monitor-form"
            hx-post="{{ route("servers.services.install", ["server" => $server]) }}"
            hx-swap="outerHTML"
            hx-select="#install-remote-monitor-form"
            class="p-6"
        >
            @csrf
            <input type="hidden" name="name" value="remote-monitor" />
            <input type="hidden" name="type" value="monitoring" />
            <input type="hidden" name="version" value="latest" />

            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __("Install Remote Monitor") }}
            </h2>

            @error("type")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    {{ __("Cancel") }}
                </x-secondary-button>

                <x-primary-button id="btn-remote-monitor" hx-disable class="ml-3">
                    {{ __("Install") }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
@endpush
