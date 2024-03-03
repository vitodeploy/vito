<div>
    <x-card-header>
        <x-slot name="title">{{ __("Firewall Rules") }}</x-slot>
        <x-slot name="description">
            {{ __("Your server's firewall rules are here. You can manage them") }}
        </x-slot>
        <x-slot name="aside">
            <livewire:firewall.create-firewall-rule :server="$server" />
        </x-slot>
    </x-card-header>
    <div x-data="" class="space-y-3">
        @foreach ($rules as $rule)
            <x-item-card>
                <div class="flex flex-grow flex-col items-start justify-center">
                    <span class="mb-1 flex items-center uppercase">
                        {{ $rule->protocol }}
                        <x-status
                            :status="$rule->type == 'allow' ? 'success' : 'danger'"
                            class="ml-1"
                        >
                            {{ $rule->type }}
                        </x-status>
                    </span>
                    <span class="text-sm text-gray-400">
                        {{ __("From") }}
                        {{ $rule->source }}/{{ $rule->mask }}
                        {{ __("Port") }} {{ $rule->port }}
                    </span>
                </div>
                <div class="flex items-center">
                    @include("livewire.firewall.partials.status", ["status" => $rule->status])
                    <div class="inline">
                        <x-icon-button
                            x-on:click="$wire.deleteId = '{{ $rule->id }}'; $dispatch('open-modal', 'delete-rule')"
                        >
                            Delete
                        </x-icon-button>
                    </div>
                </div>
            </x-item-card>
        @endforeach

        <x-item-card>
            <div class="flex flex-grow flex-col items-start justify-center">
                <span class="mb-1 flex items-center uppercase">
                    {{ __("All") }}
                    <x-status status="danger" class="ml-1">
                        {{ __("Deny") }}
                    </x-status>
                </span>
                <span class="text-sm text-gray-400">
                    {{ __("From") }} 0.0.0.0/0
                </span>
            </div>
            <div class="flex items-center">
                {{ __("Default") }}
            </div>
        </x-item-card>
        <x-confirm-modal
            name="delete-rule"
            :title="__('Confirm')"
            :description="__('Are you sure that you want to delete this rule?')"
            method="delete"
        />
    </div>
</div>
