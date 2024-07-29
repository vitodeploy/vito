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
                    <x-item-card id="cronjob-{{ $cronjob->id }}">
                        <div class="flex flex-grow flex-col items-start justify-center">
                            <div class="mb-1 text-left text-xs lowercase text-red-600 md:text-sm">
                                {{ $cronjob->command }}
                            </div>
                            <div class="text-xs text-gray-400 md:text-sm">
                                {{ $cronjob->frequencyLabel() }}
                            </div>
                        </div>
                        <div class="flex items-center">
                            @include("cronjobs.partials.status", ["status" => $cronjob->status])
                            <div class="ml-1 inline">
                                @if ($cronjob->status == \App\Enums\CronjobStatus::READY)
                                    <x-icon-button
                                        id="disable-cronjob-{{ $cronjob->id }}"
                                        data-tooltip="Disable Cronjob"
                                        hx-post="{{ route('servers.cronjobs.disable', ['server' => $server, 'cronJob' => $cronjob]) }}"
                                        hx-target="#cronjob-{{ $cronjob->id }}"
                                        hx-select="#cronjob-{{ $cronjob->id }}"
                                        hx-swap="outerHTML"
                                        hx-ext="disable-element"
                                        hx-disable-element="#disable-cronjob-{{ $cronjob->id }}"
                                    >
                                        <x-heroicon name="o-stop" class="h-5 w-5" />
                                    </x-icon-button>
                                @endif

                                @if ($cronjob->status == \App\Enums\CronjobStatus::DISABLED)
                                    <x-icon-button
                                        id="enable-cronjob-{{ $cronjob->id }}"
                                        data-tooltip="Enable Cronjob"
                                        hx-post="{{ route('servers.cronjobs.enable', ['server' => $server, 'cronJob' => $cronjob]) }}"
                                        hx-target="#cronjob-{{ $cronjob->id }}"
                                        hx-select="#cronjob-{{ $cronjob->id }}"
                                        hx-swap="outerHTML"
                                        hx-ext="disable-element"
                                        hx-disable-element="#enable-cronjob-{{ $cronjob->id }}"
                                    >
                                        <x-heroicon name="o-play" class="h-5 w-5" />
                                    </x-icon-button>
                                @endif

                                <x-icon-button
                                    data-tooltip="Delete Cronjob"
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
