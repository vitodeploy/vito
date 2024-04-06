<x-card>
    <x-slot name="title">{{ __("Update Application Log File Path") }}</x-slot>

    <x-slot name="description">
        {{ __("You must choose the path where your application log is located.") }}
    </x-slot>

    <form
        id="update-log-app-path"
        hx-post="{{ route("servers.sites.logs-app.update-path", ["server" => $server, "site" => $site]) }}"
        hx-swap="outerHTML"
        hx-select="#update-log-app-path"
        hx-ext="disable-element"
        hx-disable-element="#btn-update-log-app-path"
        class="space-y-6"
    >
        <div>
            <x-input-label for="log_app_path" :value="__('Full Path')" />
            <x-text-input
                value="{{ old('log_app_path', data_get($site, 'type_data.log_app_path')) }}"
                id="log_app_path"
                placeholder="{{ old('log_app_path', data_get($site, 'path', '/home/...')) }}"
                name="log_app_path"
                type="text"
                class="mt-1 block w-full"
                autocomplete="log_app_path"
            />
            <x-input-help>
                {{ __("It must be the full path to the file.") }}
            </x-input-help>
            @error("log_app_path")
            <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>

    </form>

    <x-slot name="actions">
        <x-primary-button id="btn-update-log-app-path" form="update-log-app-path" hx-disable>
            {{ __("Save") }}
        </x-primary-button>
    </x-slot>
</x-card>
