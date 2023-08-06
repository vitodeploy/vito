<div>
    <x-card-header>
        <x-slot name="title">{{ __("Cronjobs") }}</x-slot>
        <x-slot name="description">{{ __("Your server's Cronjobs are here. You can manage them") }}</x-slot>
        <x-slot name="aside">
            <livewire:cronjobs.create-cronjob :server="$server" />
        </x-slot>
    </x-card-header>
    <div x-data="" class="space-y-3">
        @if(count($cronjobs) > 0)
            @foreach($cronjobs as $cronjob)
                <x-item-card>
                    <div class="flex flex-grow flex-col items-start justify-center">
                        <span class="mb-1 flex items-center lowercase text-red-600">
                            {{ $cronjob->command }}
                        </span>
                        <span class="text-sm text-gray-400">
                            {{ $cronjob->frequency_label }}
                        </span>
                    </div>
                    <div class="flex items-center">
                        @include('livewire.cronjobs.partials.status', ['status' => $cronjob->status])
                        <div class="inline">
                            <x-icon-button x-on:click="$wire.deleteId = '{{ $cronjob->id }}'; $dispatch('open-modal', 'delete-cronjob')">
                                Delete
                            </x-icon-button>
                        </div>
                    </div>
                </x-item-card>
            @endforeach
            <x-confirm-modal
                name="delete-cronjob"
                :title="__('Confirm')"
                :description="__('Are you sure that you want to delete this cronjob?')"
                method="delete"
            />
        @else
            <x-simple-card>
                <div class="text-center">
                    {{ __("You haven't connected to any server providers yet!") }}
                </div>
            </x-simple-card>
        @endif
    </div>
</div>
