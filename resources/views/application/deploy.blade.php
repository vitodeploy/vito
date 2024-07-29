<div>
    @php
        $hasDeploymentScript = (bool) $site->deploymentScript;
    @endphp

    <form
        id="deploy"
        @if ($hasDeploymentScript)
            hx-post="{{ route("servers.sites.application.deploy", ["server" => $server, "site" => $site]) }}"
            hx-swap="outerHTML"
            hx-select="#deploy"
        @else
            data-tooltip="Click the Manage button to add a deployment script first"
        @endif
    >
        <x-primary-button
            class="flex items-center justify-between"
            :active="true"
            hx-disable
            :disabled="(bool) !$hasDeploymentScript"
        >
            {{ __("Deploy") }}
            <x-heroicon name="o-play-circle" class="ml-1 h-5 w-5" />
        </x-primary-button>
    </form>
</div>
