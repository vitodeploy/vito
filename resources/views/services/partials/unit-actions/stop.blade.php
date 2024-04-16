<x-icon-button
    :disabled="isset($disabled) ? $disabled : $service->status != \App\Enums\ServiceStatus::READY"
    data-tooltip="Stop Service"
    class="cursor-pointer"
    href="{{ route('servers.services.stop', ['server' => $server, 'service' => $service]) }}"
>
    <x-heroicon name="o-stop" class="h-5 w-5 text-red-400" />
</x-icon-button>
