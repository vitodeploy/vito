<div x-data="{ deleteAction: '' }">
    <x-card-header>
        <x-slot name="title">{{ __("Cronjobs") }}</x-slot>
        <x-slot name="description">
            {{ __("Your server's Cronjobs are here. You can manage them") }}
        </x-slot>
        <x-slot name="aside">
            @include("cronjobs.partials.create-cronjob")
        </x-slot>
    </x-card-header>
    <x-live id="live-cronjobs">
        <div x-data="" class="space-y-3">
            @if (count($cronjobs) > 0)
                @foreach ($cronjobs as $cronjob)
                    <x-item-card>
                        <div class="flex flex-grow flex-col items-start justify-center">
                            <span class="mb-1 flex items-center lowercase text-red-600">
                                {{ $cronjob->command }}
                            </span>
                            <span class="text-sm text-gray-400">
                                {{ $cronjob->frequencyLabel() }}
                            </span>
                        </div>
                        <div class="flex items-center">
                            @include("cronjobs.partials.status", ["status" => $cronjob->status])
                            <div class="inline">
                                <x-icon-button
                                    x-on:click="deleteAction = '{{ route('servers.cronjobs.destroy', ['server' => $server, 'cronJob' => $cronjob]) }}'; $dispatch('open-modal', 'delete-cronjob')"
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
                        {{ __("You haven't connected to any server providers yet!") }}
                    </div>
                </x-simple-card>
            @endif
        </div>
    </x-live>
    <x-confirmation-modal
        name="delete-cronjob"
        :title="__('Confirm')"
        :description="__('Are you sure that you want to delete this cronjob?')"
        method="delete"
        x-bind:action="deleteAction"
    />
</div>
