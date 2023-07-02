<div>
    <x-card-header>
        <x-slot name="title">{{ __("SSH Keys") }}</x-slot>
        <x-slot name="description">{{ __("Add or modify your ssh keys") }}</x-slot>
        <x-slot name="aside">
            <livewire:ssh-keys.add-key />
        </x-slot>
    </x-card-header>
    <div x-data="" class="space-y-3">
        @if(count($keys) > 0)
            @foreach($keys as $key)
                <x-item-card>
                    <div class="flex flex-grow flex-col items-start justify-center">
                        <span class="mb-1">{{ $key->name }}</span>
                        <span class="text-sm text-gray-400">
                            <x-datetime :value="$key->created_at" />
                        </span>
                    </div>
                    <div class="flex items-center">
                        <div class="inline">
                            <x-icon-button x-on:click="$wire.deleteId = '{{ $key->id }}'; $dispatch('open-modal', 'delete-key')">
                                <x-heroicon-o-trash class="w-4 h-4" />
                            </x-icon-button>
                        </div>
                    </div>
                </x-item-card>
            @endforeach
            <x-confirm-modal
                name="delete-key"
                :title="__('Confirm')"
                :description="__('Are you sure that you want to delete this key?')"
                method="delete"
            />
        @else
            <x-simple-card>
                <div class="text-center">
                    {{ __("You haven't connected to any keys yet!") }}
                </div>
            </x-simple-card>
        @endif
    </div>
</div>
