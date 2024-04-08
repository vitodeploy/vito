<div>
    @if ($site->deploymentScript)
        <form
            id="deploy"
            hx-post="{{ route("servers.sites.application.deploy", ["server" => $server, "site" => $site]) }}"
            hx-swap="outerHTML"
            hx-select="#deploy"
        >
            <x-secondary-button icon="o-play-circle" iconAlign="right" :active="true" hx-disable>
                {{ __("Deploy") }}
            </x-secondary-button>
        </form>
    @endif
</div>
