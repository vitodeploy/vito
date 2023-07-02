<div>
    <x-card-header>
        <x-slot name="title">{{ __("Services") }}</x-slot>
        <x-slot name="description">{{ __("All services that we installed on your server are here") }}</x-slot>
    </x-card-header>

    <div class="space-y-3">
        @foreach($services as $service)
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
                                {{ __("Actions") }}
                                <x-heroicon-m-chevron-down class="w-4 ml-1" />
                            </x-secondary-button>
                        </x-slot>

                        <x-slot name="content">
                            @if($service->status == \App\Enums\ServiceStatus::STOPPED)
                                <x-dropdown-link class="cursor-pointer" wire:click="start({{ $service->id }})">
                                    {{ __("Start") }}
                                </x-dropdown-link>
                            @endif
                            @if($service->status == \App\Enums\ServiceStatus::READY)
                                <x-dropdown-link class="cursor-pointer" wire:click="stop({{ $service->id }})">
                                    {{ __("Stop") }}
                                </x-dropdown-link>
                            @endif
                            <x-dropdown-link class="cursor-pointer" wire:click="restart({{ $service->id }})">
                                {{ __("Restart") }}
                            </x-dropdown-link>
                        </x-slot>
                    </x-dropdown>
                </div>
            </x-item-card>
        @endforeach
    </div>
</div>
