<div>
    <x-card-header>
        <x-slot name="title">Source Controls</x-slot>
        <x-slot name="description">You can connect your source controls via API Tokens</x-slot>
        <x-slot name="aside">
            <livewire:source-controls.connect />
        </x-slot>
    </x-card-header>
    <div x-data="" class="space-y-3">
        @if(count($sourceControls) > 0)
            @foreach($sourceControls as $sourceControl)
                <x-item-card>
                    <div class="flex-none">
                        <img src="{{ asset('static/images/' . $sourceControl->provider . '.svg') }}" class="h-10 w-10" alt="">
                    </div>
                    <div class="ml-3 flex flex-grow flex-col items-start justify-center">
                        <span class="mb-1">{{ $sourceControl->profile }}</span>
                        <span class="text-sm text-gray-400">
                            <x-datetime :value="$sourceControl->created_at"/>
                        </span>
                    </div>
                    <div class="flex items-center">
                        <div class="inline">
                            <x-icon-button x-on:click="$wire.deleteId = '{{ $sourceControl->id }}'; $dispatch('open-modal', 'delete-source-control')">
                                Delete
                            </x-icon-button>
                        </div>
                    </div>
                </x-item-card>
            @endforeach
            <x-confirm-modal
                name="delete-source-control"
                :title="__('Confirm')"
                :description="__('Are you sure that you want to delete this source control?')"
                method="delete"
            />
        @else
            <x-simple-card>
                <div class="text-center">
                    {{ __("You haven't connected to any server source controls yet!") }}
                </div>
            </x-simple-card>
        @endif
    </div>
</div>
