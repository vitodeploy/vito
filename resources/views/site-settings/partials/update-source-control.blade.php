<x-card>
    <x-slot name="title">{{ __("Update Source Control") }}</x-slot>

    <x-slot name="description">
        {{ __("You can switch the source control profile (token) in case of token expiration. Keep in mind that it must be the same account and provider") }}
    </x-slot>

    <form
        id="update-source-control"
        hx-post="{{ route("servers.sites.settings.source-control", ["server" => $server, "site" => $site]) }}"
        hx-swap="outerHTML"
        hx-select="#update-source-control"
        hx-ext="disable-element"
        hx-disable-element="#btn-update-source-control"
        class="space-y-6"
    >
        @include(
            "sites.partials.create.fields.source-control",
            [
                "sourceControls" => \App\Models\SourceControl::query()
                    ->where("provider", $site->sourceControl()?->provider)
                    ->get(),
            ]
        )
    </form>

    <x-slot name="actions">
        <x-primary-button id="btn-update-source-control" form="update-source-control" hx-disable>
            {{ __("Save") }}
        </x-primary-button>
    </x-slot>
</x-card>
