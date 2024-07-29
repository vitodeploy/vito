<x-icon-button
    :disabled="isset($disabled) ? $disabled : $service->status != \App\Enums\ServiceStatus::STOPPED"
    data-tooltip="Start Service"
    class="cursor-pointer"
    href="{{ route('servers.services.start', ['server' => $server, 'service' => $service]) }}"
>
    <x-heroicon name="o-play" class="h-5 w-5 text-green-400" />
</x-icon-button>
