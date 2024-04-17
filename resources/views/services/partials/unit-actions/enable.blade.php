<x-icon-button
    :disabled="isset($disabled) ? $disabled : $service->status != \App\Enums\ServiceStatus::DISABLED"
    data-tooltip="Enable Service"
    class="cursor-pointer"
    href="{{ route('servers.services.enable', ['server' => $server, 'service' => $service]) }}"
>
    <x-heroicon name="o-check" class="h-5 w-5" />
</x-icon-button>
