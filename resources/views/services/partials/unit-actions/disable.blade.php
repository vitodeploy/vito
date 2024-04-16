<x-icon-button
    :disabled="isset($disabled) ? $disabled : false"
    data-tooltip="Disable Service"
    class="cursor-pointer"
    href="{{ route('servers.services.disable', ['server' => $server, 'service' => $service]) }}"
>
    <x-heroicon name="o-no-symbol" class="h-5 w-5" />
</x-icon-button>
