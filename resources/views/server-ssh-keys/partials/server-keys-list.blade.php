<div x-data="{ deleteAction: '' }">
    <x-card-header>
        <x-slot name="title">{{ __("SSH Keys") }}</x-slot>
        <x-slot name="description">
            {{ __("Add or modify your ssh keys") }}
        </x-slot>
        <x-slot name="aside">
            <div class="flex flex-col items-end lg:flex-row lg:items-center">
                <div class="mb-2 lg:mb-0 lg:mr-2">
                    @include("server-ssh-keys.partials.add-new-key")
                </div>
                <div>
                    @include("server-ssh-keys.partials.add-existing-key")
                </div>
            </div>
        </x-slot>
    </x-card-header>
    <x-live id="live-server-ssh-keys">
        <div x-data="" class="space-y-3">
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
                            @include("server-ssh-keys.partials.status", ["status" => $key->pivot->status])
                            <div class="inline">
                                <x-icon-button
                                    x-on:click="deleteAction = '{{ route('servers.ssh-keys.destroy', ['server' => $server, 'sshKey' => $key]) }}'; $dispatch('open-modal', 'delete-key')"
                                >
                                    <x-heroicon name="o-trash" class="h-5 w-5" />
                                </x-icon-button>
                            </div>
                        </div>
                    </x-item-card>
                @endforeach
            @else
                <x-simple-card>
                    <div class="text-center">
                        {{ __("You haven't connected to any keys yet!") }}
                    </div>
                </x-simple-card>
            @endif
        </div>
    </x-live>

    <x-confirmation-modal
        name="delete-key"
        :title="__('Confirm')"
        :description="__('Are you sure that you want to delete this key?')"
        method="delete"
        x-bind:action="deleteAction"
    />
</div>
