<div>
    <x-card-header>
        <x-slot name="title">{{ __("SSH Keys") }}</x-slot>
        <x-slot name="description">
            {{ __("Add or modify your ssh keys") }}
        </x-slot>
        <x-slot name="aside">
            @include("settings.ssh-keys.partials.add-key")
        </x-slot>
    </x-card-header>
    <div x-data="{ deleteAction: '' }" class="space-y-3">
        @if (count($keys) > 0)
            @foreach ($keys as $key)
                <x-item-card>
                    <div class="flex flex-grow flex-col items-start justify-center">
                        <span class="mb-1">{{ $key->name }}</span>
                        <span class="text-sm text-gray-400">
                            <x-datetime :value="$key->created_at" />
                        </span>
                    </div>
                    <div class="flex items-center">
                        <div class="inline">
                            <x-icon-button
                                x-on:click="deleteAction = '{{ route('settings.ssh-keys.delete', $key->id) }}'; $dispatch('open-modal', 'delete-ssh-key')"
                            >
                                <x-heroicon name="o-trash" class="h-5 w-5" />
                            </x-icon-button>
                        </div>
                    </div>
                </x-item-card>
            @endforeach

            @include("settings.ssh-keys.partials.delete-ssh-key")
        @else
            <x-simple-card>
                <div class="text-center">
                    {{ __("You haven't connected to any keys yet!") }}
                </div>
            </x-simple-card>
        @endif
    </div>
</div>
