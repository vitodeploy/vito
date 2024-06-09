<div>
    <x-card-header>
        <x-slot name="title">{{ __("API Keys") }}</x-slot>
        <x-slot name="description">
            {{ __("Add or modify your API keys") }}
        </x-slot>
        <x-slot name="aside">
            @include("settings.api-v1.partials.add-apikey")
        </x-slot>
    </x-card-header>
    <div x-data="{ deleteAction: '', regenerateAction: '' }" class="space-y-3">
        @if (count($keys) > 0)
            @foreach ($keys as $key)
                <x-item-card>
                    <div class="flex flex-grow flex-col items-start justify-center">
                        <span class="mb-1">{{__('Description') }}</span>
                        <span class="text-sm text-gray-400">
                            {{ $key->description }}
                        </span>
                    </div>
                    <div class="flex flex-grow flex-col items-start justify-center">
                        <span class="mb-1">{{__('API Key') }}</span>
                        <span class="text-sm text-gray-400">
                            <code>{{ $key->secret }}</code>
                        </span>
                    </div>
                    <div class="flex flex-grow flex-col items-center justify-center">
                        <span class="mb-1">{{__('Status') }}</span>
                        <span class="text-sm text-gray-400">
                            <x-status status="{{ $key->getHumanStatus() == 'Active' ? 'success' : 'danger' }}" class="ml-1">
                                {{ $key->getHumanStatus() }}
                            </x-status>
                        </span>
                    </div>
                    <div class="flex flex-grow flex-col items-start justify-center">
                        <span class="mb-1">{{ __('Last Used') }}</span>
                        <span class="text-sm text-gray-400">
                            {{ $key->lastUsedAt() }}
                        </span>
                    </div>
                    <div class="flex flex-grow flex-col items-start justify-center">
                        <span class="mb-1">{{ __('Created at') }}</span>
                        <span class="text-sm text-gray-400">
                            <x-datetime :value="$key->created_at" />
                        </span>
                    </div>
                    <div class="flex items-center">
                        <div class="inline">
                            <x-icon-button
                                x-on:click="regenerateAction = '{{ route('api-v1.update', $key->id) }}'; $dispatch('open-modal', 'regenerate-api-key')"
                            >
                                <x-heroicon name="o-arrow-path" class="h-5 w-5" />
                            </x-icon-button>
                        </div>
                        <div class="inline">
                            <x-icon-button
                                x-on:click="deleteAction = '{{ route('api-v1.destroy', $key->id) }}'; $dispatch('open-modal', 'delete-api-key')"
                            >
                                <x-heroicon name="o-trash" class="h-5 w-5" />
                            </x-icon-button>
                        </div>
                    </div>
                </x-item-card>
            @endforeach

            @include("settings.api-v1.partials.delete-apikey")
            @include("settings.api-v1.partials.regenerate-apikey")
        @else
            <x-simple-card>
                <div class="text-center">
                    {{ __("You haven't connected to any API keys yet!") }}
                </div>
            </x-simple-card>
        @endif
    </div>
</div>
