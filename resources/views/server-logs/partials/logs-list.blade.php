@php
    if (isset($site)) {
        $logs = $site
            ->logs()
            ->latest()
            ->paginate(10);
    } else if (isset($remote)) {
        $logs = $server
            ->logs()
            ->remote()
            ->latest()
            ->paginate(10);
    } else {
        $logs = $server
            ->logs()
            ->remote(false)
            ->latest()
            ->paginate(10);
    }
@endphp

<div x-data="{
    deleteAction: ''
}">
    <x-card-header>
        <x-slot name="title">
            @isset($pageTitle)
                {{ $pageTitle }}
            @else
                {{ __("Logs") }}
            @endisset
        </x-slot>
    </x-card-header>
    <x-live id="live-server-logs">
        <x-table>
            <x-tr>
                <x-th>{{ isset($remote) ? __("Path") : __("Event") }}</x-th>
                <x-th>{{ __("Date") }}</x-th>
                <x-th></x-th>
            </x-tr>
            @foreach ($logs as $log)
                <x-tr>
                    <x-td>{{  isset($remote) ? $log->name : $log->type }}</x-td>
                    <x-td>
                        <x-datetime :value="$log->created_at" />
                    </x-td>
                    <x-td>
                        <x-icon-button
                            x-on:click="$dispatch('open-modal', 'show-log'); document.getElementById('log-content').firstChild.innerHTML = '';"
                            hx-get="{{ route('servers.logs.show', ['server' => $server, 'serverLog' => $log->id]) }}"
                            hx-target="#log-content"
                            hx-select="#log-content"
                        >
                            <x-heroicon name="o-eye" class="h-5 w-5" />
                        </x-icon-button>

                        @isset($remote)
                            <x-icon-button
                                x-on:click="deleteAction = '{{ route('servers.logs.remote.destroy', ['server' => $server, 'serverLog' => $log->id]) }}'; $dispatch('open-modal', 'delete-remote-log')"
                            >
                                <x-heroicon name="o-trash" class="h-5 w-5" />
                            </x-icon-button>
                        @endif
                    </x-td>
                </x-tr>
            @endforeach
        </x-table>
        @if ($logs instanceof \Illuminate\Pagination\LengthAwarePaginator)
            <div class="mt-5">
                {{ $logs->withQueryString()->links() }}
            </div>
        @endif
    </x-live>
    <div id="delete"></div>
    <x-modal name="show-log" max-width="4xl">
        <div class="p-6">
            <h2 class="mb-5 text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __("View Log") }}
            </h2>
            <div id="log-content">
                <x-console-view>
                    @if (session()->has("content"))
                        {{ session("content") }}
                    @endif
                </x-console-view>
            </div>
            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    {{ __("Close") }}
                </x-secondary-button>
            </div>
        </div>
    </x-modal>
    <x-confirmation-modal
        name="delete-remote-log"
        title="Confirm"
        description="Are you sure that you want to delete this log?"
        method="delete"
        x-bind:action="deleteAction"
    />
</div>
