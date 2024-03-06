<x-card>
    <x-slot name="title">{{ __("Update VHost") }}</x-slot>

    <x-slot name="description">
        {{ __("You can change your site's VHost configuration") }}
    </x-slot>

    <div
        id="update-vhost-container"
        hx-get="{{ route("servers.sites.settings.vhost", ["server" => $server, "site" => $site]) }}"
        hx-trigger="load"
        hx-target="#vhost"
        hx-select="#vhost"
        hx-swap="outerHTML"
    >
        <form
            id="update-vhost"
            hx-post="{{ route("servers.sites.settings.vhost", ["server" => $server, "site" => $site]) }}"
            hx-swap="outerHTML"
            hx-target="#update-vhost-container"
            hx-select="#update-vhost-container"
            class="space-y-6"
        >
            <div>
                <x-textarea id="vhost" name="vhost" rows="10" class="mt-1 block w-full">
                    {{ session()->has("vhost") ? session()->get("vhost") : "Loading..." }}
                </x-textarea>
                @error("vhost")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>
        </form>
    </div>

    <x-slot name="actions">
        <x-primary-button form="update-vhost" hx-disable>
            {{ __("Save") }}
        </x-primary-button>
    </x-slot>
</x-card>
