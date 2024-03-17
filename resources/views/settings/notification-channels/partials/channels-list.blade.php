<div>
    <x-card-header>
        <x-slot name="title">{{ __("Notification Channels") }}</x-slot>
        <x-slot name="description">
            {{ __("Add or modify your notification channels") }}
        </x-slot>
        <x-slot name="aside">
            @include("settings.notification-channels.partials.add-channel")
        </x-slot>
    </x-card-header>
    <div x-data="{ deleteAction: '' }" class="space-y-3">
        @if (count($channels) > 0)
            @foreach ($channels as $channel)
                <x-item-card>
                    <div class="flex-none text-gray-600 dark:text-gray-300">
                        @include("settings.notification-channels.partials.icons." . $channel->provider)
                    </div>
                    <div class="ml-3 flex flex-grow flex-col items-start justify-center">
                        <span class="mb-1">{{ $channel->label }}</span>
                        <span class="text-sm text-gray-400">
                            <x-datetime :value="$channel->created_at" />
                        </span>
                    </div>
                    <div class="flex items-center">
                        <div class="inline">
                            <x-icon-button
                                x-on:click="deleteAction = '{{ route('notification-channels.delete', $channel->id) }}'; $dispatch('open-modal', 'delete-channel')"
                            >
                                <x-heroicon name="o-trash" class="h-5 w-5" />
                            </x-icon-button>
                        </div>
                    </div>
                </x-item-card>
            @endforeach

            @include("settings.notification-channels.partials.delete-channel")
        @else
            <x-simple-card>
                <div class="text-center">
                    {{ __("You haven't connected to any channels yet!") }}
                </div>
            </x-simple-card>
        @endif
    </div>
</div>
