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

    <x-live id="live-servers-list">
        @if (count($servers) > 0)
            <div class="space-y-3">
                @foreach ($servers as $server)
                    <a href="{{ route("servers.show", ["server" => $server]) }}" class="block">
                        <x-item-card>
                            <div class="flex-none">
                                <img
                                    src="{{ asset("static/images/" . $server->provider . ".svg") }}"
                                    class="h-10 w-10"
                                    alt=""
                                />
                            </div>
                            <div class="ml-3 flex flex-grow flex-col items-start justify-center">
                                <span class="mb-1">{{ $server->name }}</span>
                                <span class="text-sm text-gray-400">
                                    {{ $server->ip }}
                                </span>
                            </div>
                            <div class="flex items-center">
                                <div class="inline">
                                    @include("servers.partials.server-status", ["server" => $server])
                                </div>
                            </div>
                        </x-item-card>
                    </a>
                @endforeach
            </div>
        @else
            <x-simple-card>
                <div class="text-center">
                    {{ __("You don't have any servers yet!") }}
                </div>
            </x-simple-card>
        @endif
    </x-live>
</x-container>
