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
                @foreach ($sites as $site)
                    <a href="{{ route("servers.sites.show", ["server" => $server, "site" => $site]) }}" class="block">
                        <x-item-card>
                            <div class="flex-none">
                                <img
                                    src="{{ asset("static/images/" . $site->type . ".svg") }}"
                                    class="h-10 w-10"
                                    alt=""
                                />
                            </div>
                            <div class="ml-3 flex flex-grow flex-col items-start justify-center">
                                <span class="mb-1">{{ $site->domain }}</span>
                                <span class="text-sm text-gray-400">
                                    <x-datetime :value="$site->created_at" />
                                </span>
                            </div>
                            <div class="flex items-center">
                                <div class="inline">
                                    @include("sites.partials.status", ["status" => $site->status])
                                </div>
                            </div>
                        </x-item-card>
                    </a>
                @endforeach
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
