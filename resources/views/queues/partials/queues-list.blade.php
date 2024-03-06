<div x-data="{ deleteAction: '' }">
    <x-card-header>
        <x-slot name="title">{{ __("Queues") }}</x-slot>
        <x-slot name="description">
            {{ __("You can manage and create queues for your site via supervisor") }}
        </x-slot>
        <x-slot name="aside">
            @include("queues.partials.create-queue")
        </x-slot>
    </x-card-header>
    <x-live id="live-queues">
        <div x-data="" class="space-y-3">
            @if (count($queues) > 0)
                @foreach ($queues as $queue)
                    <x-item-card>
                        <div class="flex flex-grow flex-col items-start justify-center">
                            <span class="mb-1 flex items-center lowercase text-red-600">
                                {{ $queue->command }}
                            </span>
                            <span class="text-sm text-gray-400">{{ __("User:") }} {{ $queue->user }}</span>
                        </div>
                        <div class="flex items-center">
                            @include("queues.partials.status", ["status" => $queue->status])
                            <div id="queue-actions-{{ $queue->id }}" class="inline-flex">
                                <x-icon-button
                                    id="stop-{{ $queue->id }}"
                                    hx-post="{{ route('servers.sites.queues.action', ['server' => $server, 'site' => $site, 'queue' => $queue, 'action' => 'stop']) }}"
                                    hx-swap="outerHTML"
                                    hx-select="#queue-actions-{{ $queue->id }}"
                                >
                                    Stop
                                </x-icon-button>
                                <x-icon-button
                                    id="resume-{{ $queue->id }}"
                                    hx-post="{{ route('servers.sites.queues.action', ['server' => $server, 'site' => $site, 'queue' => $queue, 'action' => 'start']) }}"
                                    hx-swap="outerHTML"
                                    hx-select="#queue-actions-{{ $queue->id }}"
                                >
                                    Start
                                </x-icon-button>
                                <x-icon-button
                                    id="restart-{{ $queue->id }}"
                                    hx-post="{{ route('servers.sites.queues.action', ['server' => $server, 'site' => $site, 'queue' => $queue, 'action' => 'restart']) }}"
                                    hx-swap="outerHTML"
                                    hx-select="#queue-actions-{{ $queue->id }}"
                                >
                                    Restart
                                </x-icon-button>
                                <x-icon-button
                                    x-on:click="deleteAction = '{{ route('servers.sites.queues.destroy', ['server' => $server, 'site' => $site, 'queue' => $queue]) }}'; $dispatch('open-modal', 'delete-queue')"
                                >
                                    <x-heroicon-o-trash class="h-5 w-5" />
                                </x-icon-button>
                            </div>
                        </div>
                    </x-item-card>
                @endforeach
            @else
                <x-simple-card>
                    <div class="text-center">
                        {{ __("You don't have any queues yet!") }}
                    </div>
                </x-simple-card>
            @endif
        </div>
    </x-live>
    <x-confirmation-modal
        name="delete-queue"
        :title="__('Confirm')"
        :description="__('Are you sure that you want to delete this queue?')"
        method="delete"
        x-bind:action="deleteAction"
    />
</div>
