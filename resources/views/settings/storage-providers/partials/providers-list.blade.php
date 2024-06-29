<div>
    <x-card-header>
        <x-slot name="title">Storage Providers</x-slot>
        <x-slot name="description">You can connect to your storage providers</x-slot>
        <x-slot name="aside">
            @include("settings.storage-providers.partials.connect-provider")
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
                        <div class="mb-1 flex items-center">
                            {{ $provider->profile }}
                            @if (! $provider->project_id)
                                <x-status status="disabled" class="ml-2">GLOBAL</x-status>
                            @endif
                        </div>
                        <span class="text-sm text-gray-400">
                            <x-datetime :value="$provider->created_at" />
                        </span>
                    </div>
                    <div class="flex items-center">
                        <div class="inline">
                            <x-icon-button
                                id="edit-{{ $provider->id }}"
                                hx-get="{{ route('settings.storage-providers', ['edit' => $provider->id]) }}"
                                hx-replace-url="true"
                                hx-select="#edit"
                                hx-target="#edit"
                                hx-ext="disable-element"
                                hx-disable-element="#edit-{{ $provider->id }}"
                            >
                                <x-heroicon name="o-pencil" class="h-5 w-5" />
                            </x-icon-button>
                            <x-icon-button
                                x-on:click="deleteAction = '{{ route('settings.storage-providers.delete', $provider->id) }}'; $dispatch('open-modal', 'delete-provider')"
                            >
                                <x-heroicon name="o-trash" class="h-5 w-5" />
                            </x-icon-button>
                        </div>
                    </div>
                </x-item-card>
            @endforeach

            @include("settings.storage-providers.partials.delete-storage-provider")

            <div id="edit">
                @if (isset($editProvider))
                    @include("settings.storage-providers.partials.edit-provider", ["storageProvider" => $editProvider])
                @endif
            </div>
        @else
            <x-simple-card>
                <div class="text-center">
                    {{ __("You haven't connected to any storage providers yet!") }}
                </div>
            </x-simple-card>
        @endif
    </div>
</div>
