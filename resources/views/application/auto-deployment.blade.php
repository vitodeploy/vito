<div>
    @if ($site->deploymentScript)
        <x-dropdown>
            <x-slot name="trigger">
                <x-secondary-button>
                    {{ __("Auto Deployment") }}
                    <x-heroicon name="o-chevron-down" class="ml-1 h-4 w-4" />
                </x-secondary-button>
            </x-slot>
            <x-slot name="content">
                <div id="auto-deployment">
                    <x-dropdown-link
                        class="cursor-pointer"
                        hx-post="{{ route('servers.sites.application.auto-deployment', ['server' => $server, 'site' => $site]) }}"
                        hx-swap="outerHTML"
                        hx-target="#auto-deployment"
                        hx-select="#auto-deployment"
                    >
                        {{ __("Enable") }}
                        @if ($site->isAutoDeployment())
                            <x-heroicon name="o-check" class="ml-1 h-5 w-5 text-green-600" />
                        @endif
                    </x-dropdown-link>
                    <x-dropdown-link
                        class="cursor-pointer"
                        hx-delete="{{ route('servers.sites.application.auto-deployment', ['server' => $server, 'site' => $site]) }}"
                        hx-swap="outerHTML"
                        hx-target="#auto-deployment"
                    >
                        {{ __("Disable") }}
                        @if (! $site->isAutoDeployment())
                            <x-heroicon name="o-check" class="ml-1 h-5 w-5 text-green-600" />
                        @endif
                    </x-dropdown-link>
                </div>
            </x-slot>
        </x-dropdown>
    @endif
</div>
