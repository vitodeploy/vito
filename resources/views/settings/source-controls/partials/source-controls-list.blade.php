<div>
    <x-card-header>
        <x-slot name="title">Source Controls</x-slot>
        <x-slot name="description">You can connect your source controls via API Tokens</x-slot>
        <x-slot name="aside">
            @include("settings.source-controls.partials.connect")
        </x-slot>
    </x-card-header>
    <div x-data="{ deleteAction: '' }" class="space-y-3">
        @if (count($sourceControls) > 0)
            @foreach ($sourceControls as $sourceControl)
                <x-item-card>
                    <div class="flex-none text-gray-600 dark:text-gray-300">
                        @include("settings.source-controls.partials.icons." . $sourceControl->provider . "-icon")
                    </div>
                    <div class="ml-3 flex flex-grow flex-col items-start justify-center">
                        <div class="mb-1 flex items-center">
                            {{ $sourceControl->profile }}
                            @if (! $sourceControl->project_id)
                                <x-status status="disabled" class="ml-2">GLOBAL</x-status>
                            @endif
                        </div>
                        <span class="text-sm text-gray-400">
                            <x-datetime :value="$sourceControl->created_at" />
                        </span>
                    </div>
                    <div class="flex items-center">
                        <div class="inline">
                            <x-icon-button
                                id="edit-{{ $sourceControl->id }}"
                                hx-get="{{ route('settings.source-controls', ['edit' => $sourceControl->id]) }}"
                                hx-replace-url="true"
                                hx-select="#edit"
                                hx-target="#edit"
                                hx-ext="disable-element"
                                hx-disable-element="#edit-{{ $sourceControl->id }}"
                            >
                                <x-heroicon name="o-pencil" class="h-5 w-5" />
                            </x-icon-button>
                            <x-icon-button
                                x-on:click="deleteAction = '{{ route('settings.source-controls.delete', $sourceControl->id) }}'; $dispatch('open-modal', 'delete-source-control')"
                            >
                                <x-heroicon name="o-trash" class="h-5 w-5" />
                            </x-icon-button>
                        </div>
                    </div>
                </x-item-card>
            @endforeach

            @include("settings.source-controls.partials.delete-source-control")

            <div id="edit">
                @if (isset($editSourceControl))
                    @include("settings.source-controls.partials.edit-source-control", ["sourceControl" => $editSourceControl])
                @endif
            </div>
        @else
            <x-simple-card>
                <div class="text-center">
                    {{ __("You haven't connected to any server source controls yet!") }}
                </div>
            </x-simple-card>
        @endif
    </div>
</div>
