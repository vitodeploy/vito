<div x-data="">
    <x-card-header>
        <x-slot name="title">{{ __("Logs") }}</x-slot>
    </x-card-header>
    <x-table>
        <tr>
            <x-th>{{ __("Event") }}</x-th>
            <x-th>{{ __("Date") }}</x-th>
            <x-th></x-th>
        </tr>
        @foreach($logs as $log)
            <tr>
                <x-td>{{ $log->type }}</x-td>
                <x-td>
                    <x-datetime :value="$log->created_at" />
                </x-td>
                <x-td>
                    <x-icon-button wire:click="showLog({{ $log->id }})" wire:loading.attr="disabled">
                        <x-heroicon-o-eye class="w-6 h-6" />
                    </x-icon-button>
                </x-td>
            </tr>
        @endforeach
    </x-table>
    @if(is_null($count))
        <div class="mt-5">
            {{ $logs->withQueryString()->links() }}
        </div>
    @endif
    <x-modal name="show-log" max-width="4xl">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-5">
                {{ __('View Log') }}
            </h2>
            <x-console-view>{{ $logContent }}</x-console-view>
            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    {{ __('Close') }}
                </x-secondary-button>
            </div>
        </div>
    </x-modal>
</div>
