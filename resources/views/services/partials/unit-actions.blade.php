<x-icon-button
    data-tooltip="Restart Service"
    class="cursor-pointer"
    href="{{ route('servers.services.restart', ['server' => $server, 'service' => $service]) }}"
>
    <x-heroicon name="o-arrow-path" class="h-5 w-5" />
</x-icon-button>

<x-icon-button
    :disabled="$service->status != \App\Enums\ServiceStatus::STOPPED"
    data-tooltip="Start Service"
    class="cursor-pointer"
    href="{{ route('servers.services.start', ['server' => $server, 'service' => $service]) }}"
>
    <x-heroicon name="o-play" class="h-5 w-5 text-green-400" />
</x-icon-button>

<x-icon-button
    data-tooltip="Stop Service"
    :disabled="$service->status != \App\Enums\ServiceStatus::READY"
    class="cursor-pointer"
    href="{{ route('servers.services.stop', ['server' => $server, 'service' => $service]) }}"
>
    <x-heroicon name="o-stop" class="h-5 w-5 text-red-400" />
</x-icon-button>

<x-icon-button
    :disabled="$service->status != \App\Enums\ServiceStatus::DISABLED"
    data-tooltip="Enable Service"
    class="cursor-pointer"
    href="{{ route('servers.services.enable', ['server' => $server, 'service' => $service]) }}"
>
    <x-heroicon name="o-check" class="h-5 w-5" />
</x-icon-button>

<x-icon-button
    data-tooltip="Disable Service"
    class="cursor-pointer"
    href="{{ route('servers.services.disable', ['server' => $server, 'service' => $service]) }}"
>
    <x-heroicon name="o-no-symbol" class="h-5 w-5" />
</x-icon-button>
