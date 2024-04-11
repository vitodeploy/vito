<div>
    @if ($site->deploymentScript)
        <form
            id="deploy"
            hx-post="{{ route("servers.sites.application.deploy", ["server" => $server, "site" => $site]) }}"
            hx-swap="outerHTML"
            hx-select="#deploy"
        >
            <x-primary-button class="flex items-center justify-between" :active="true" hx-disable>
                {{ __("Deploy") }} <x-heroicon name="o-play-circle" class="ml-1 h-5 w-5" />
            </x-primary-button>
        </form>
    @endif
</div>
