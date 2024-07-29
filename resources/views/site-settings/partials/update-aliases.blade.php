<x-card>
    <x-slot name="title">{{ __("Update Aliases") }}</x-slot>

    <x-slot name="description">
        {{ __("Add/Remove site aliases") }}
    </x-slot>

    <form
        id="update-aliases"
        hx-post="{{ route("servers.sites.settings.aliases", ["server" => $server, "site" => $site]) }}"
        hx-swap="outerHTML"
        hx-select="#update-aliases"
        hx-ext="disable-element"
        hx-disable-element="#btn-update-aliases"
        class="space-y-6"
    >
        @include(
            "sites.partials.create.fields.aliases",
            [
                "aliases" => $site->aliases,
            ]
        )
    </form>

    <x-slot name="actions">
        <x-primary-button id="btn-update-aliases" form="update-aliases" hx-disable>
            {{ __("Save") }}
        </x-primary-button>
    </x-slot>
</x-card>
