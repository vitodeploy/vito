<x-card>
    <x-slot name="title">{{ __("Update VHost") }}</x-slot>

    <x-slot name="description">
        {{ __("You can change your site's VHost configuration") }}
    </x-slot>

    <form
        id="update-vhost"
        hx-post="{{ route("servers.sites.settings.vhost", ["server" => $server, "site" => $site]) }}"
        hx-swap="outerHTML"
        hx-select="#update-vhost"
        class="space-y-6"
        hx-ext="disable-element"
        hx-disable-element="#btn-update-vhost"
    >
        <div
            hx-get="{{ route("servers.sites.settings.vhost", ["server" => $server, "site" => $site]) }}"
            hx-trigger="load"
            hx-target="#vhost-container"
            hx-select="#vhost-container"
            hx-swap="outerHTML"
        >
            <div id="vhost-container">
                <x-textarea id="vhost" name="vhost" rows="10" class="mt-1 block min-h-[400px] w-full font-mono">
                    {{ session()->has("vhost") ? session()->get("vhost") : "Loading..." }}
                </x-textarea>
            </div>
            @error("vhost")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>
    </form>

    <x-slot name="actions">
        <x-primary-button form="update-vhost" id="btn-update-vhost" hx-disable>
            {{ __("Save") }}
        </x-primary-button>
    </x-slot>
</x-card>
