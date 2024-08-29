<aside
    id="logo-sidebar"
    class="fixed left-0 top-0 z-40 h-screen w-64 -translate-x-full border-r border-gray-200 bg-white pt-20 transition-transform dark:border-gray-700 dark:bg-gray-800 sm:translate-x-0"
    aria-label="Sidebar"
>
    <div class="h-full overflow-y-auto bg-white px-3 pb-4 dark:bg-gray-800">
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
                            :active="request()->routeIs('servers.databases') || request()->routeIs('servers.databases.backups')"
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
                            {{ __("Server Settings") }}
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
                <x-sidebar-link :href="route('servers')" :active="request()->routeIs('servers')">
                    <x-heroicon name="o-server" class="h-6 w-6" />
                    <span class="ml-2">Servers</span>
                </x-sidebar-link>
            </li>

            <li>
                <x-sidebar-link :href="route('scripts.index')" :active="request()->routeIs('scripts.*')">
                    <x-heroicon name="o-bolt" class="h-6 w-6" />
                    <span class="ml-2">Scripts</span>
                </x-sidebar-link>
            </li>

            <li>
                <x-sidebar-link :href="route('profile')" :active="request()->routeIs('profile')">
                    <x-heroicon name="o-user-circle" class="h-6 w-6" />
                    <span class="ml-2">Profile</span>
                </x-sidebar-link>
            </li>

            @if (auth()->user()->isAdmin())
                <li>
                    <x-sidebar-link
                        :href="route('settings.users.index')"
                        :active="request()->routeIs('settings.users*')"
                    >
                        <x-heroicon name="o-user-group" class="h-6 w-6" />
                        <span class="ml-2">Users</span>
                    </x-sidebar-link>
                </li>
                <li>
                    <x-sidebar-link
                        :href="route('settings.projects')"
                        :active="request()->routeIs('settings.projects')"
                    >
                        <x-heroicon name="o-inbox-stack" class="h-6 w-6" />
                        <span class="ml-2">Projects</span>
                    </x-sidebar-link>
                </li>
                <li>
                    <x-sidebar-link
                        :href="route('settings.server-providers')"
                        :active="request()->routeIs('settings.server-providers')"
                    >
                        <x-heroicon name="o-server-stack" class="h-6 w-6" />
                        <span class="ml-2">Server Providers</span>
                    </x-sidebar-link>
                </li>
                <li>
                    <x-sidebar-link
                        :href="route('settings.source-controls')"
                        :active="request()->routeIs('settings.source-controls')"
                    >
                        <x-heroicon name="o-code-bracket" class="h-6 w-6" />
                        <span class="ml-2">Source Controls</span>
                    </x-sidebar-link>
                </li>
                <li>
                    <x-sidebar-link
                        :href="route('settings.storage-providers')"
                        :active="request()->routeIs('settings.storage-providers')"
                    >
                        <x-heroicon name="o-circle-stack" class="h-6 w-6" />
                        <span class="ml-2">Storage Providers</span>
                    </x-sidebar-link>
                </li>
                <li>
                    <x-sidebar-link
                        :href="route('settings.notification-channels')"
                        :active="request()->routeIs('settings.notification-channels')"
                    >
                        <x-heroicon name="o-bell" class="h-6 w-6" />
                        <span class="ml-2">Notification Channels</span>
                    </x-sidebar-link>
                </li>
                <li>
                    <x-sidebar-link
                        :href="route('settings.ssh-keys')"
                        :active="request()->routeIs('settings.ssh-keys')"
                    >
                        <x-heroicon name="o-key" class="h-6 w-6" />
                        <span class="ml-2">SSH Keys</span>
                    </x-sidebar-link>
                </li>
                <li>
                    <x-sidebar-link :href="route('settings.tags')" :active="request()->routeIs('settings.tags')">
                        <x-heroicon name="o-tag" class="h-6 w-6" />
                        <span class="ml-2">Tags</span>
                    </x-sidebar-link>
                </li>
            @endif
        </ul>
    </div>
</aside>
