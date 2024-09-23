<x-filament-panels::page wire:poll.5s="refresh">
    @if ($server->isInstalling())
        <x-filament::section>
            <x-slot:heading>
                <div class="flex items-center">
                    <x-filament::loading-indicator class="mr-2 size-5 text-primary-400" />
                    Installing Server
                </div>
            </x-slot>
            <x-progress-bar :value="$server->progress" />
        </x-filament::section>
    @else
        @livewire(\App\Web\Resources\Server\Widgets\ServerDetails::class, ["server" => $server])
    
        @livewire(\App\Web\Resources\Server\Widgets\UpdateServerInfo::class, ["server" => $server])
    @endif
</x-filament-panels::page>
