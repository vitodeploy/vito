<div>
    <x-card-header>
        <x-slot name="title">{{ __("Services") }}</x-slot>
        <x-slot name="description">
            {{ __("All services that we installed on your server are here") }}
        </x-slot>
        <x-slot name="aside"></x-slot>
    </x-card-header>

    <x-live id="live-services">
        <div class="space-y-3">
            @foreach ($services as $service)
                <x-item-card>
                    <div class="flex-none">
                        <img
                            src="{{ asset("static/images/" . $service->name . ".svg") }}"
                            class="h-10 w-10"
                            alt=""
                        />
                    </div>
                    <div
                        class="ml-3 flex flex-grow flex-col items-start justify-center"
                    >
                        <div class="flex items-center">
                            <div class="mr-2">
                                {{ $service->name }}:{{ $service->version }}
                            </div>
                            @include("services.partials.status", ["status" => $service->status])
                        </div>
                    </div>
                    <div class="flex items-center">
                        <x-dropdown>
                            <x-slot name="trigger">
                                <x-secondary-button>
                                    {{ __("Actions") }}
                                </x-secondary-button>
                            </x-slot>

                            <x-slot name="content">
                                @if ($service->unit)
                                    @if ($service->status == \App\Enums\ServiceStatus::STOPPED)
                                        <x-dropdown-link
                                            class="cursor-pointer"
                                            href="{{ route('servers.services.start', ['server' => $server, 'service' => $service]) }}"
                                        >
                                            {{ __("Start") }}
                                        </x-dropdown-link>
                                    @endif

                                    @if ($service->status == \App\Enums\ServiceStatus::READY)
                                        <x-dropdown-link
                                            class="cursor-pointer"
                                            href="{{ route('servers.services.stop', ['server' => $server, 'service' => $service]) }}"
                                        >
                                            {{ __("Stop") }}
                                        </x-dropdown-link>
                                    @endif

                                    <x-dropdown-link
                                        class="cursor-pointer"
                                        href="{{ route('servers.services.restart', ['server' => $server, 'service' => $service]) }}"
                                    >
                                        {{ __("Restart") }}
                                    </x-dropdown-link>
                                @endif
                            </x-slot>
                        </x-dropdown>
                    </div>
                </x-item-card>
            @endforeach
        </div>
    </x-live>
</div>
