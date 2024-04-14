<x-icon-button
    data-tooltip="Stop Service"
    :disabled="$service->status != \App\Enums\ServiceStatus::READY"
    class="cursor-pointer"
    href="{{ route('servers.services.stop', ['server' => $server, 'service' => $service]) }}"
>
    <x-heroicon name="o-stop" class="h-5 w-5 text-red-400" />
</x-icon-button>
