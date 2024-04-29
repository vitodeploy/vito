@if (auth()->user()->currentProject &&auth()->user()->can("view", auth()->user()->currentProject))
    <div data-tooltip="Servers" class="cursor-pointer">
        <x-dropdown width="full">
            <x-slot:trigger>
                <div>
                    <div
                        class="block w-full rounded-md border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                    >
                        {{ isset($server) ? $server->name : "Select Server" }}
                    </div>
                    <button type="button" class="absolute inset-y-0 right-0 flex items-center pr-2">
                        <x-heroicon name="o-chevron-down" class="h-4 w-4 text-gray-400" />
                    </button>
                </div>
            </x-slot>
            <x-slot:content>
                @foreach (auth()->user()->currentProject->servers as $s)
                    <x-dropdown-link class="relative" :href="route('servers.show', ['server' => $s])">
                        <span class="block truncate">{{ ucfirst($s->name) }}</span>
                        @if (isset($server) && $server->id == $s->id)
                            <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-primary-600">
                                <x-heroicon name="o-check" class="h-5 w-5" />
                            </span>
                        @endif
                    </x-dropdown-link>
                @endforeach

                <x-dropdown-link href="{{ route('servers') }}">
                    {{ __("Servers List") }}
                </x-dropdown-link>
                <x-dropdown-link href="{{ route('servers.create') }}">
                    {{ __("Create a Server") }}
                </x-dropdown-link>
            </x-slot>
        </x-dropdown>
    </div>
@endif
