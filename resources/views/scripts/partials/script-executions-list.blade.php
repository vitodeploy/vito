<x-container>
    <x-card-header>
        <x-slot name="title">Script Executions</x-slot>
        <x-slot name="description">Here you can see the list of the latest executions of your script</x-slot>
    </x-card-header>

    <x-live id="script-executions" interval="5s">
        @if (count($executions) > 0)
            <div id="scripts-list" x-data="{}">
                <x-table>
                    <x-thead>
                        <x-tr>
                            <x-th>Date</x-th>
                            <x-th>Status</x-th>
                            <x-th></x-th>
                        </x-tr>
                    </x-thead>
                    <x-tbody>
                        @foreach ($executions as $execution)
                            <x-tr>
                                <x-td>
                                    <x-datetime :value="$execution->created_at" />
                                </x-td>
                                <x-td>
                                    @include("scripts.partials.script-execution-status", ["status" => $execution->status])
                                </x-td>
                                <x-td class="text-right">
                                    <x-icon-button
                                        x-on:click="$dispatch('open-modal', 'show-log')"
                                        id="show-log-{{ $execution->id }}"
                                        hx-get="{{ route('scripts.log', ['script' => $script, 'execution' => $execution]) }}"
                                        hx-target="#show-log-content"
                                        hx-select="#show-log-content"
                                        hx-swap="outerHTML"
                                        data-tooltip="Logs"
                                    >
                                        <x-heroicon name="o-eye" class="h-5 w-5" />
                                    </x-icon-button>
                                </x-td>
                            </x-tr>
                        @endforeach
                    </x-tbody>
                </x-table>
            </div>
        @else
            <x-simple-card>
                <div class="text-center">This script hasn't been executed yet!</div>
            </x-simple-card>
        @endif
        <div class="mt-5">
            {{ $executions->withQueryString()->links() }}
        </div>
    </x-live>
    <x-modal name="show-log" max-width="4xl">
        <div class="p-6" id="show-log-content">
            <h2 class="mb-5 text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __("View Log") }}
            </h2>
            <x-console-view>{{ session()->get("content") }}</x-console-view>
            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    {{ __("Close") }}
                </x-secondary-button>
            </div>
        </div>
    </x-modal>
</x-container>
