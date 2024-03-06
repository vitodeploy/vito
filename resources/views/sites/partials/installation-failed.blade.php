<x-card>
    <x-slot name="title">
        <span class="text-red-600">{{ __("Installation Failed!") }}</span>
    </x-slot>
    <x-slot name="description">
        {{ __("Your site's installation failed") }}
    </x-slot>
    <div class="mt-5 flex items-center justify-center">
        <x-secondary-button :href="route('servers.logs', ['server' => $site->server])" class="mr-2">
            {{ __("View Logs") }}
        </x-secondary-button>
        @include("site-settings.partials.delete-site")
    </div>
</x-card>
