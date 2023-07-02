<div>
    <x-card-header>
        <x-slot name="title">{{ __("Notification Channels") }}</x-slot>
        <x-slot name="description">{{ __("Add or modify your notification channels") }}</x-slot>
        <x-slot name="aside">
            <livewire:notification-channels.add-channel />
        </x-slot>
    </x-card-header>
    <div x-data="" class="space-y-3">
        @if(count($channels) > 0)
            @foreach($channels as $channel)
                <x-item-card>
                    <div class="flex-none">
                        <img src="{{ asset('static/images/' . $channel->provider . '.svg') }}" class="h-10 w-10" alt="">
                    </div>
                    <div class="ml-3 flex flex-grow flex-col items-start justify-center">
                        <span class="mb-1">{{ $channel->label }}</span>
                        <span class="text-sm text-gray-400">
                            <x-datetime :value="$channel->created_at" />
                        </span>
                    </div>
                    <div class="flex items-center">
                        <div class="inline">
                            <x-icon-button x-on:click="$wire.deleteId = '{{ $channel->id }}'; $dispatch('open-modal', 'delete-channel')">
                                <x-heroicon-o-trash class="w-4 h-4" />
                            </x-icon-button>
                        </div>
                    </div>
                </x-item-card>
            @endforeach
            <x-confirm-modal
                name="delete-channel"
                :title="__('Confirm')"
                :description="__('Are you sure that you want to delete this channel?')"
                method="delete"
            />
        @else
            <x-simple-card>
                <div class="text-center">
                    {{ __("You haven't connected to any channels yet!") }}
                </div>
            </x-simple-card>
        @endif
    </div>
</div>
