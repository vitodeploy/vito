<x-card>
    <x-slot name="title">
        <span class="text-red-600">{{ __("Installation Failed!") }}</span>
    </x-slot>
    <x-slot name="description">{{ __("Your server installation failed") }}</x-slot>
    <div class="text-center">
        {{ __("The installation has been failed on step:") }}
        <span class="font-bold">{{ $server->progress_step }} ({{ $server->progress }}%)</span>
    </div>
    <div class="mt-5 flex items-center justify-center">
        <x-secondary-button :href="route('servers.logs', ['server' => $server])" class="mr-2">{{ __("View Logs") }}</x-secondary-button>
        <livewire:servers.delete-server :server="$server" />
    </div>
</x-card>
