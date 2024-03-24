<div>
    @if ($site->deploymentScript)
        <form
            id="deploy"
            hx-post="{{ route("servers.sites.application.deploy", ["server" => $server, "site" => $site]) }}"
            hx-swap="outerHTML"
            hx-select="#deploy"
        >
            <x-primary-button hx-disable>
                {{ __("Deploy") }}
            </x-primary-button>
        </form>
    @endif
</div>
