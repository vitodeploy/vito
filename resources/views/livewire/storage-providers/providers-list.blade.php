<div>
    <x-card-header>
        <x-slot name="title">Storage Providers</x-slot>
        <x-slot name="description">You can connect to your storage providers</x-slot>
        <x-slot name="aside">
            <livewire:storage-providers.connect-provider />
        </x-slot>
    </x-card-header>
    <div x-data="" class="space-y-3">
        @if(count($providers) > 0)
            @foreach($providers as $provider)
                <x-item-card>
                    <div class="flex-none">
                        <img src="{{ asset('static/images/' . $provider->provider . '.svg') }}" class="h-10 w-10" alt="">
                    </div>
                    <div class="ml-3 flex flex-grow flex-col items-start justify-center">
                        <span class="mb-1">{{ $provider->profile }}</span>
                        <span class="text-sm text-gray-400">
                            <x-datetime :value="$provider->created_at"/>
                        </span>
                    </div>
                    <div class="flex items-center">
                        <div class="inline">
                            <x-icon-button x-on:click="$wire.deleteId = '{{ $provider->id }}'; $dispatch('open-modal', 'delete-provider')">
                                Delete
                            </x-icon-button>
                        </div>
                    </div>
                </x-item-card>
            @endforeach
            <x-confirm-modal
                name="delete-provider"
                :title="__('Confirm')"
                :description="__('Are you sure that you want to delete this provider?')"
                method="delete"
            />
        @else
            <x-simple-card>
                <div class="text-center">
                    {{ __("You haven't connected to any storage providers yet!") }}
                </div>
            </x-simple-card>
        @endif
    </div>
</div>
