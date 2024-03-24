<div>
    <x-card-header>
        <x-slot name="title">Server Providers</x-slot>
        <x-slot name="description">You can connect to your server providers to create servers using their APIs</x-slot>
        <x-slot name="aside">
            @include("settings.server-providers.partials.connect-provider")
        </x-slot>
    </x-card-header>
    <div x-data="{ deleteAction: '' }" class="space-y-3">
        @if (count($providers) > 0)
            @foreach ($providers as $provider)
                <x-item-card>
                    <div class="flex-none">
                        <img
                            src="{{ asset("static/images/" . $provider->provider . ".svg") }}"
                            class="h-10 w-10"
                            alt=""
                        />
                    </div>
                    <div class="ml-3 flex flex-grow flex-col items-start justify-center">
                        <span class="mb-1">{{ $provider->profile }}</span>
                        <span class="text-sm text-gray-400">
                            <x-datetime :value="$provider->created_at" />
                        </span>
                    </div>
                    <div class="flex items-center">
                        <div class="inline">
                            <x-icon-button
                                x-on:click="deleteAction = '{{ route('server-providers.delete', $provider->id) }}'; $dispatch('open-modal', 'delete-provider')"
                            >
                                <x-heroicon name="o-trash" class="h-5 w-5" />
                            </x-icon-button>
                        </div>
                    </div>
                </x-item-card>
            @endforeach

            @include("settings.server-providers.partials.delete-provider")
        @else
            <x-simple-card>
                <div class="text-center">
                    {{ __("You haven't connected to any server providers yet!") }}
                </div>
            </x-simple-card>
        @endif
    </div>
</div>
