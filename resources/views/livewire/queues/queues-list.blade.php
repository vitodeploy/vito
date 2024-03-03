<div>
    <x-card-header>
        <x-slot name="title">{{ __("Queues") }}</x-slot>
        <x-slot name="description">
            {{ __("You can manage and create queues for your site via supervisor") }}
        </x-slot>
        <x-slot name="aside">
            <livewire:queues.create-queue :site="$site" />
        </x-slot>
    </x-card-header>
    <div x-data="" class="space-y-3">
        @if (count($queues) > 0)
            @foreach ($queues as $queue)
                <x-item-card>
                    <div
                        class="flex flex-grow flex-col items-start justify-center"
                    >
                        <span
                            class="mb-1 flex items-center lowercase text-red-600"
                        >
                            {{ $queue->command }}
                        </span>
                        <span class="text-sm text-gray-400">
                            {{ __("User:") }} {{ $queue->user }}
                        </span>
                    </div>
                    <div class="flex items-center">
                        @include("livewire.queues.partials.status", ["status" => $queue->status])
                        <div class="inline-flex">
                            <x-icon-button
                                wire:click="start({{ $queue }})"
                                wire:loading.attr="disabled"
                            >
                                Resume
                            </x-icon-button>
                            <x-icon-button
                                wire:click="stop({{ $queue }})"
                                wire:loading.attr="disabled"
                            >
                                Stop
                            </x-icon-button>
                            <x-icon-button
                                wire:click="restart({{ $queue }})"
                                wire:loading.attr="disabled"
                            >
                                Restart
                            </x-icon-button>
                            <x-icon-button
                                x-on:click="$wire.deleteId = '{{ $queue->id }}'; $dispatch('open-modal', 'delete-queue')"
                            >
                                Delete
                            </x-icon-button>
                        </div>
                    </div>
                </x-item-card>
            @endforeach

            <x-confirm-modal
                name="delete-queue"
                :title="__('Confirm')"
                :description="__('Are you sure that you want to delete this queue?')"
                method="delete"
            />
        @else
            <x-simple-card>
                <div class="text-center">
                    {{ __("You don't have any queues yet!") }}
                </div>
            </x-simple-card>
        @endif
    </div>
</div>
