<x-container>
    <x-card-header>
        <x-slot name="title">Servers</x-slot>
        <x-slot name="description">Here you can see your servers list and manage them</x-slot>
        <x-slot name="aside">
            <x-primary-button :href="route('servers.create')">
                {{ __("Create a Server") }}
            </x-primary-button>
        </x-slot>
    </x-card-header>

    <div class="space-y-3">
        <x-live id="live-servers-list">
            @if (count($servers) > 0)
                <div class="space-y-3">
                    <x-table>
                        <x-thead>
                            <x-tr>
                                <x-th>Name</x-th>
                                <x-th>IP</x-th>
                                <x-th>Tags</x-th>
                                <x-th>Status</x-th>
                                <x-th></x-th>
                            </x-tr>
                        </x-thead>
                        <x-tbody>
                            @foreach ($servers as $server)
                                <x-tr>
                                    <x-td>
                                        <div class="flex items-center">
                                            <img
                                                src="{{ asset("static/images/" . $server->provider . ".svg") }}"
                                                class="mr-1 h-5 w-5"
                                                alt=""
                                            />
                                            <a
                                                href="{{ route("servers.show", ["server" => $server]) }}"
                                                class="hover:underline"
                                            >
                                                {{ $server->name }}
                                            </a>
                                        </div>
                                    </x-td>
                                    <x-td>{{ $server->ip }}</x-td>
                                    <x-td>
                                        @include("settings.tags.tags", ["taggable" => $server, "oobOff" => true])
                                    </x-td>
                                    <x-td>
                                        @include("servers.partials.server-status", ["server" => $server])
                                    </x-td>
                                    <x-td>
                                        <div class="flex items-center justify-end">
                                            <x-icon-button
                                                :href="route('servers.show', ['server' => $server])"
                                                data-tooltip="Show Server"
                                            >
                                                <x-heroicon name="o-eye" class="h-5 w-5" />
                                            </x-icon-button>
                                            <x-icon-button
                                                :href="route('servers.settings', ['server' => $server])"
                                                data-tooltip="Settings"
                                            >
                                                <x-heroicon name="o-wrench-screwdriver" class="h-5 w-5" />
                                            </x-icon-button>
                                        </div>
                                    </x-td>
                                </x-tr>
                            @endforeach
                        </x-tbody>
                    </x-table>
                </div>
            @else
                <x-simple-card>
                    <div class="text-center">
                        {{ __("You don't have any servers yet!") }}
                    </div>
                </x-simple-card>
            @endif
        </x-live>
    </div>
</x-container>
