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
                        @if($provider->provider == \App\Enums\StorageProvider::FTP)
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 text-gray-600 dark:text-gray-200">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                        @else
                            <img src="{{ asset('static/images/' . $provider->provider . '.svg') }}" class="h-10 w-10" alt="">
                        @endif
                    </div>
                    <div class="ml-3 flex flex-grow flex-col items-start justify-center">
                        <span class="mb-1">{{ $provider->profile }}</span>
                        <span class="text-sm text-gray-400">
                            <x-datetime :value="$provider->created_at" />
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
