<x-card>
    <x-slot name="title">{{ __("Application Log Viewer") }}</x-slot>

    <x-slot name="description">
        {{ __("Below you can see the log") }}
    </x-slot>

    <form
        id="update-log-app-viewer"
        hx-get="{{ route("servers.sites.logs-app.file", ["server" => data_get($server, 'id'), "site" => $site]) }}"
        hx-target="#log-viewer"
        hx-select="#log-viewer"
        hx-swap="innerHTML"
        hx-trigger="submit"
        class="space-y-6"
    >
        <x-slot name="actions">
            <x-primary-button
                form="update-log-app-viewer"
                id="btn-reload-app-log"
                onclick="loading()"
            >
                {{ __("Reload") }}
            </x-primary-button>
        </x-slot>
    </form>

    <div
        hx-get="{{ route("servers.sites.logs-app.file", ["server" => $server, "site" => $site]) }}"
        hx-trigger="load"
        hx-target="#log-viewer"
        hx-select="#log-viewer"
        hx-swap="innerHTML"
    >
        <div id="log-app-viewer-container">
            <x-textarea id="log-viewer" readonly name="log-viewer" rows="10" class="mt-1 block min-h-[400px] w-full">
                {{ __('Loading...') }}
            </x-textarea>
        </div>
        @error("log-viewer")
        <x-input-error class="mt-2" :messages="$message" />
        @enderror
    </div>

        <script>
            const STICKY_OFFSET = 160;
            function loading() {
                let viewer = document.getElementById("log-viewer")
                viewer.value = 'Loading...'
            }
            document.addEventListener("htmx:after-swap", (event) => {
                if (!(event.target instanceof HTMLElement)) {
                    return;
                }

                let viewer = document.getElementById("log-viewer");
                viewer.value = event.detail.xhr.response;
                viewer.scrollTop = viewer.scrollHeight  - STICKY_OFFSET;
            });
        </script>

</x-card>
