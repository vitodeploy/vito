<ul class="space-y-2 font-medium">
    <li>
        @include("layouts.partials.server-select")
    </li>
    <x-hr />
    @if (isset($server))
        <li>
            <x-sidebar-link
                :href="route('servers.show', ['server' => $server])"
                :active="request()->routeIs('servers.show')"
            >
                <x-heroicon name="o-home" class="h-6 w-6" />
                <span class="ml-2">Overview</span>
            </x-sidebar-link>
        </li>
        @if ($server->webserver())
            <li>
                <x-sidebar-link
                    :href="route('servers.sites', ['server' => $server])"
                    :active="request()->routeIs('servers.sites') || request()->is('servers/*/sites/*')"
                >
                    <x-heroicon name="o-globe-alt" class="h-6 w-6" />
                    <span class="ml-2">
                        {{ __("Sites") }}
                    </span>
                </x-sidebar-link>
            </li>
        @endif

        @if ($server->database()?->status == \App\Enums\ServiceStatus::READY)
            <li>
                <x-sidebar-link
                    :href="route('servers.databases', ['server' => $server])"
                    :active="request()->routeIs('servers.databases') ||
                    request()->routeIs('servers.databases.backups')"
                >
                    <x-heroicon name="o-circle-stack" class="h-6 w-6" />
                    <span class="ml-2">
                        {{ __("Databases") }}
                    </span>
                </x-sidebar-link>
            </li>
        @endif

        <li>
            <x-sidebar-link
                :href="route('servers.php', ['server' => $server])"
                :active="request()->routeIs('servers.php')"
            >
                <x-heroicon name="o-code-bracket" class="h-6 w-6" />
                <span class="ml-2">
                    {{ __("PHP") }}
                </span>
            </x-sidebar-link>
        </li>

        @if ($server->firewall())
            <li>
                <x-sidebar-link
                    :href="route('servers.firewall', ['server' => $server])"
                    :active="request()->routeIs('servers.firewall')"
                >
                    <x-heroicon name="o-fire" class="h-6 w-6" />
                    <span class="ml-2">
                        {{ __("Firewall") }}
                    </span>
                </x-sidebar-link>
            </li>
        @endif

        <li>
            <x-sidebar-link
                :href="route('servers.cronjobs', ['server' => $server])"
                :active="request()->routeIs('servers.cronjobs')"
            >
                <x-heroicon name="o-clock" class="h-6 w-6" />
                <span class="ml-2">
                    {{ __("Cronjobs") }}
                </span>
            </x-sidebar-link>
        </li>

        <li>
            <x-sidebar-link
                :href="route('servers.ssh-keys', ['server' => $server])"
                :active="request()->routeIs('servers.ssh-keys')"
            >
                <x-heroicon name="o-key" class="h-6 w-6" />
                <span class="ml-2">
                    {{ __("Server SSH Keys") }}
                </span>
            </x-sidebar-link>
        </li>

        <li>
            <x-sidebar-link
                :href="route('servers.services', ['server' => $server])"
                :active="request()->routeIs('servers.services')"
            >
                <x-heroicon name="o-cog-6-tooth" class="h-6 w-6" />
                <span class="ml-2">
                    {{ __("Services") }}
                </span>
            </x-sidebar-link>
        </li>

        @if ($server->monitoring())
            <li>
                <x-sidebar-link
                    :href="route('servers.metrics', ['server' => $server])"
                    :active="request()->routeIs('servers.metrics')"
                >
                    <x-heroicon name="o-chart-bar" class="h-6 w-6" />
                    <span class="ml-2">
                        {{ __("Metrics") }}
                    </span>
                </x-sidebar-link>
            </li>
        @endif

        <li>
            <x-sidebar-link
                :href="route('servers.console', ['server' => $server])"
                :active="request()->routeIs('servers.console')"
            >
                <x-heroicon name="o-command-line" class="h-6 w-6" />
                <span class="ml-2">
                    {{ __("Console") }}
                </span>
            </x-sidebar-link>
        </li>

        <li>
            <x-sidebar-link
                :href="route('servers.settings', ['server' => $server])"
                :active="request()->routeIs('servers.settings')"
            >
                <x-heroicon name="o-wrench-screwdriver" class="h-6 w-6" />
                <span class="ml-2">
                    {{ __("Settings") }}
                </span>
            </x-sidebar-link>
        </li>

        <li>
            <x-sidebar-link
                :href="route('servers.logs', ['server' => $server])"
                :active="request()->routeIs('servers.logs*')"
            >
                <x-heroicon name="o-square-3-stack-3d" class="h-6 w-6" />
                <span class="ml-2">
                    {{ __("Logs") }}
                </span>
            </x-sidebar-link>
        </li>
        <x-hr />
    @endif

    <li>
        <x-sidebar-link :href="route('profile')" :active="request()->routeIs('profile')">
            <x-heroicon name="o-user-circle" class="h-6 w-6" />
            <span class="ml-2">Profile</span>
        </x-sidebar-link>
    </li>
    <li>
        <x-sidebar-link :href="route('projects')" :active="request()->routeIs('projects')">
            <x-heroicon name="o-inbox-stack" class="h-6 w-6" />
            <span class="ml-2">Projects</span>
        </x-sidebar-link>
    </li>
    <li>
        <x-sidebar-link :href="route('server-providers')" :active="request()->routeIs('server-providers')">
            <x-heroicon name="o-server-stack" class="h-6 w-6" />
            <span class="ml-2">Server Providers</span>
        </x-sidebar-link>
    </li>
    <li>
        <x-sidebar-link :href="route('source-controls')" :active="request()->routeIs('source-controls')">
            <x-heroicon name="o-code-bracket" class="h-6 w-6" />
            <span class="ml-2">Source Controls</span>
        </x-sidebar-link>
    </li>
    <li>
        <x-sidebar-link :href="route('storage-providers')" :active="request()->routeIs('storage-providers')">
            <x-heroicon name="o-circle-stack" class="h-6 w-6" />
            <span class="ml-2">Storage Providers</span>
        </x-sidebar-link>
    </li>
    <li>
        <x-sidebar-link :href="route('notification-channels')" :active="request()->routeIs('notification-channels')">
            <x-heroicon name="o-bell" class="h-6 w-6" />
            <span class="ml-2">Notification Channels</span>
        </x-sidebar-link>
    </li>
    <li>
        <x-sidebar-link :href="route('ssh-keys')" :active="request()->routeIs('ssh-keys')">
            <x-heroicon name="o-key" class="h-6 w-6" />
            <span class="ml-2">SSH Keys</span>
        </x-sidebar-link>
    </li>
</ul>
