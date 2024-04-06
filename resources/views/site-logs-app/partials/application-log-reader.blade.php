<x-card>
    <x-slot name="title">{{ __("Application Log Viewer") }}</x-slot>

    <x-slot name="description">
        {{ __("Below you can see the log") }}
    </x-slot>

    <form
        id="update-log-app-viewer"
        hx-post="{{ route("servers.sites.logs-app.file", ["server" => $server, "site" => $site]) }}"
        hx-swap="outerHTML"
        hx-select="#update-log-app-viewer"
        class="space-y-6"
        hx-ext="disable-element"
        hx-disable-element="#btn-update-log-app-viewer"
    >
        <div
            hx-get="{{ route("servers.sites.logs-app.file", ["server" => $server, "site" => $site]) }}"
            hx-trigger="load"
            hx-target="#log-app-viewer-container"
            hx-select="#log-app-viewer-container"
            hx-swap="outerHTML"
        >
            <div id="log-app-viewer-container">
                <x-textarea id="vhost" name="vhost" rows="10" class="mt-1 block min-h-[400px] w-full">
                    {{ session()->has("log-app-viewer") ? session()->get("log-app-viewer") : "Loading..." }}
                </x-textarea>
            </div>
            @error("vhost")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>
    </form>

    <x-slot name="actions">
        <x-primary-button form="reload-app-log" id="btn-reload-app-log" hx-disable>
            {{ __("Reload") }}
        </x-primary-button>
    </x-slot>
</x-card>
