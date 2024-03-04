<div x-data="">
    <x-card-header>
        <x-slot name="title">{{ __("Deployments") }}</x-slot>
    </x-card-header>
    <x-table>
        <tr>
            <x-th>{{ __("Commit") }}</x-th>
            <x-th>{{ __("Date") }}</x-th>
            <x-th>{{ __("Status") }}</x-th>
            <x-th></x-th>
        </tr>
        @foreach ($deployments as $deployment)
            <tr>
                <x-td>
                    <a
                        href="{{ $deployment->commit_data["url"] }}"
                        target="_blank"
                        class="font-semibold text-primary-600"
                    >
                        {{ $deployment->commit_data["message"] }}
                    </a>
                </x-td>
                <x-td>
                    <x-datetime :value="$deployment->created_at" />
                </x-td>
                <x-td>
                    <div class="inline-flex">
                        @include("livewire.application.partials.deployment-status", ["status" => $deployment->status])
                    </div>
                </x-td>
                <x-td>
                    @if ($deployment->status != \App\Enums\DeploymentStatus::DEPLOYING)
                        <x-icon-button wire:click="showLog({{ $deployment->id }})" wire:loading.attr="disabled">
                            Logs
                        </x-icon-button>
                    @endif
                </x-td>
            </tr>
        @endforeach
    </x-table>
    <div class="mt-5">
        {{ $deployments->withQueryString()->links() }}
    </div>
    <x-modal name="show-log" max-width="4xl">
        <div class="p-6">
            <h2 class="mb-5 text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __("View Log") }}
            </h2>
            <x-console-view>{{ $logContent }}</x-console-view>
            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    {{ __("Close") }}
                </x-secondary-button>
            </div>
        </div>
    </x-modal>
</div>
