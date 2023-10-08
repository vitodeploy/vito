<div>
    <x-card-header>
        <x-slot name="title">{{ __('Services') }}</x-slot>
        <x-slot name="description">{{ __('All services that we installed on your server are here') }}</x-slot>
        <x-slot name="aside">
            <x-dropdown>
                <x-slot name="trigger">
                    <x-primary-button>
                        {{ __('Install Service') }}
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 ml-1">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </x-primary-button>
                </x-slot>
                <x-slot name="content">
                    <x-dropdown-link class="cursor-pointer" x-on:click="$dispatch('open-modal', 'install-phpmyadmin')">
                        {{ __('PHPMyAdmin') }}
                    </x-dropdown-link>
                </x-slot>
            </x-dropdown>
            <livewire:services.install-p-h-p-my-admin :server="$server" />
        </x-slot>
    </x-card-header>

    <div class="space-y-3">
        @foreach ($services as $service)
            <x-item-card>
                <div class="flex-none">
                    <img src="{{ asset('static/images/' . $service->name . '.svg') }}" class="h-10 w-10" alt="">
                </div>
                <div class="ml-3 flex flex-grow flex-col items-start justify-center">
                    <div class="flex items-center">
                        <div class="mr-2">{{ $service->name }}:{{ $service->version }}</div>
                        @include('livewire.services.partials.status', ['status' => $service->status])
                    </div>
                </div>
                <div class="flex items-center">
                    <x-dropdown>
                        <x-slot name="trigger">
                            <x-secondary-button>
                                {{ __('Actions') }}
                            </x-secondary-button>
                        </x-slot>

                        <x-slot name="content">
                            @if($service->unit)
                                @if ($service->status == \App\Enums\ServiceStatus::STOPPED)
                                    <x-dropdown-link class="cursor-pointer" wire:click="start({{ $service->id }})">
                                        {{ __('Start') }}
                                    </x-dropdown-link>
                                @endif
                                @if ($service->status == \App\Enums\ServiceStatus::READY)
                                    <x-dropdown-link class="cursor-pointer" wire:click="stop({{ $service->id }})">
                                        {{ __('Stop') }}
                                    </x-dropdown-link>
                                @endif
                                <x-dropdown-link class="cursor-pointer" wire:click="restart({{ $service->id }})">
                                    {{ __('Restart') }}
                                </x-dropdown-link>
                            @else
                                <x-dropdown-link class="cursor-pointer" wire:click="uninstall({{ $service->id }})">
                                    {{ __('Uninstall') }}
                                </x-dropdown-link>
                            @endif
                        </x-slot>
                    </x-dropdown>
                </div>
            </x-item-card>
        @endforeach
    </div>
</div>
