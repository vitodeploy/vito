<x-card id="server-details">
    <x-slot name="title">{{ __("Details") }}</x-slot>
    <x-slot name="description">
        {{ __("More details about your server") }}
    </x-slot>
    <div class="flex items-center justify-between">
        <div>{{ __("Created At") }}</div>
        <div>
            <x-datetime :value="$server->created_at" />
        </div>
    </div>
    <div>
        <div class="py-5">
            <div class="border-t border-gray-200 dark:border-gray-700"></div>
        </div>
    </div>
    <div class="flex items-center justify-between">
        <div>{{ __("Last Update Checked") }}</div>
        <div>
            @if ($server->last_update_check)
                <x-datetime :value="$server->last_update_check" />
            @else
                -
            @endif
        </div>
    </div>
    <div>
        <div class="py-5">
            <div class="border-t border-gray-200 dark:border-gray-700"></div>
        </div>
    </div>
    <div id="available-updates" class="flex items-center justify-between">
        <div>{{ __("Available Updates") }} ({{ $server->updates }})</div>
        <div class="flex flex-col items-end md:flex-row md:items-center">
            @if ($server->updates > 0)
                <x-primary-button
                    id="btn-update-server"
                    hx-post="{{ route('servers.settings.update', $server) }}"
                    hx-swap="outerHTML"
                    hx-target="#server-details"
                    hx-select="#server-details"
                    hx-ext="disable-element"
                    hx-disable-element="#btn-update-server"
                >
                    {{ __("Update") }}
                </x-primary-button>
            @endif

            <x-secondary-button
                id="btn-check-updates"
                class="mt-2 md:ml-2 md:mt-0"
                hx-post="{{ route('servers.settings.check-updates', $server) }}"
                hx-swap="outerHTML"
                hx-target="#server-details"
                hx-select="#server-details"
                hx-ext="disable-element"
                hx-disable-element="#btn-check-updates"
            >
                {{ __("Check") }}
            </x-secondary-button>
        </div>
    </div>
    <div>
        <div class="py-5">
            <div class="border-t border-gray-200 dark:border-gray-700"></div>
        </div>
    </div>
    <div class="flex items-center justify-between">
        <div>{{ __("Provider") }}</div>
        <div class="capitalize">{{ $server->provider }}</div>
    </div>
    <div>
        <div class="py-5">
            <div class="border-t border-gray-200 dark:border-gray-700"></div>
        </div>
    </div>
    <div class="flex items-center justify-between">
        <div>{{ __("Server ID") }}</div>
        <div class="flex items-center">
            <span class="rounded-md bg-gray-100 p-1 dark:bg-gray-700">
                {{ $server->id }}
            </span>
            {{-- <span class="ml-2">{{ __("You will need this when you use the API") }}</span> --}}
        </div>
    </div>
    <div>
        <div class="py-5">
            <div class="border-t border-gray-200 dark:border-gray-700"></div>
        </div>
    </div>
    <div class="flex items-center justify-between">
        <div>{{ __("Status") }}</div>
        <div class="flex items-center">
            @include("servers.partials.server-status")
            <div class="ml-2 inline-flex">
                @include("server-settings.partials.check-connection")
            </div>
        </div>
    </div>
    <div>
        <div class="py-5">
            <div class="border-t border-gray-200 dark:border-gray-700"></div>
        </div>
    </div>
    <div class="flex items-center justify-between">
        <div>{{ __("Reboot Server") }}</div>
        <div class="flex items-center">
            <div class="inline-flex">
                @include("server-settings.partials.reboot-server")
            </div>
        </div>
    </div>
    <div>
        <div class="py-5">
            <div class="border-t border-gray-200 dark:border-gray-700"></div>
        </div>
    </div>
    <div class="flex items-center justify-between">
        <div>{{ __("Tags") }}</div>
        <div>
            @include("settings.tags.tags", ["taggable" => $server, "edit" => true])
        </div>
    </div>
</x-card>
