<div>
    <x-card-header>
        <x-slot name="title">Sites</x-slot>
        <x-slot name="description">Your sites will appear here. You can see the details and manage them</x-slot>
        <x-slot name="aside">
            <x-primary-button :href="route('servers.sites.create', ['server' => $server])">
                {{ __("Create Site") }}
            </x-primary-button>
        </x-slot>
    </x-card-header>

    <x-live id="live-sites">
        @if (count($sites) > 0)
            <div class="space-y-3">
                <x-table>
                    <x-thead>
                        <x-tr>
                            <x-th>Domain</x-th>
                            <x-th>Date</x-th>
                            <x-th>Tags</x-th>
                            <x-th>Status</x-th>
                            <x-th></x-th>
                        </x-tr>
                    </x-thead>
                    <x-tbody>
                        @foreach ($sites as $site)
                            <x-tr>
                                <x-td>
                                    <div class="flex items-center">
                                        <img
                                            src="{{ asset("static/images/" . $site->type . ".svg") }}"
                                            class="mr-1 h-5 w-5"
                                            alt=""
                                        />
                                        <a
                                            href="{{ route("servers.sites.show", ["server" => $server, "site" => $site]) }}"
                                            class="hover:underline"
                                        >
                                            {{ $site->domain }}
                                        </a>
                                    </div>
                                </x-td>
                                <x-td>
                                    <x-datetime :value="$site->created_at" />
                                </x-td>
                                <x-td>
                                    @include("settings.tags.tags", ["taggable" => $site, "oobOff" => true])
                                </x-td>
                                <x-td>
                                    @include("sites.partials.status", ["status" => $site->status])
                                </x-td>
                                <x-td>
                                    <div class="flex items-center justify-end">
                                        <x-icon-button
                                            :href="route('servers.sites.show', ['server' => $server, 'site' => $site])"
                                            data-tooltip="Show Site"
                                        >
                                            <x-heroicon name="o-eye" class="h-5 w-5" />
                                        </x-icon-button>
                                        <x-icon-button
                                            :href="route('servers.sites.settings', ['server' => $server, 'site' => $site])"
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
                    {{ __("You don't have any sites yet!") }}
                </div>
            </x-simple-card>
        @endif
    </x-live>
</div>
