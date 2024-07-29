@php
    $deployments = $site
        ->deployments()
        ->latest()
        ->paginate(10);
@endphp

<div x-data="">
    <x-card-header>
        <x-slot name="title">{{ __("Deployments") }}</x-slot>
    </x-card-header>
    <x-live id="live-deployments">
        <x-table>
            <x-thead>
                <x-tr>
                    <x-th>{{ __("Commit") }}</x-th>
                    <x-th>{{ __("Date") }}</x-th>
                    <x-th>{{ __("Status") }}</x-th>
                    <x-th></x-th>
                </x-tr>
            </x-thead>
            <x-tbody>
                @foreach ($deployments as $deployment)
                    <x-tr>
                        <x-td class="truncate">
                            <a
                                href="{{ $deployment->commit_data["url"] ?? "#" }}"
                                target="_blank"
                                class="block max-w-[500px] truncate font-semibold text-primary-600"
                            >
                                {{ $deployment->commit_data["message"] ?? "No message" }}
                            </a>
                        </x-td>
                        <x-td>
                            <x-datetime :value="$deployment->created_at" />
                        </x-td>
                        <x-td>
                            <div class="inline-flex">
                                @include("application.partials.deployment-status", ["status" => $deployment->status])
                            </div>
                        </x-td>
                        <x-td>
                            <x-icon-button
                                x-on:click="$dispatch('open-modal', 'show-log')"
                                id="show-log-{{ $deployment->id }}"
                                hx-get="{{ route('servers.sites.application.deployment.log', ['server' => $server, 'site' => $site, 'deployment' => $deployment]) }}"
                                hx-target="#show-log-content"
                                hx-select="#show-log-content"
                                hx-swap="outerHTML"
                            >
                                <x-heroicon name="o-eye" class="h-5 w-5" />
                            </x-icon-button>
                        </x-td>
                    </x-tr>
                @endforeach
            </x-tbody>
        </x-table>
    </x-live>
    <div class="mt-5">
        {{ $deployments->withQueryString()->links() }}
    </div>
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
</div>
