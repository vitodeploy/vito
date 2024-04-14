<x-icon-button
    data-tooltip="Restart Service"
    class="cursor-pointer"
    href="{{ route('servers.services.restart', ['server' => $server, 'service' => $service]) }}"
>
    <x-heroicon name="o-arrow-path" class="h-5 w-5" />
</x-icon-button>
