<x-site-layout :site="$site">
    <x-slot name="pageTitle">{{ __("Settings") }}</x-slot>

    <livewire:sites.change-php-version :site="$site" />

    @if ($site->source_control_id)
        <livewire:sites.update-source-control-provider :site="$site" />
    @endif

    <livewire:sites.update-v-host :site="$site" />

    <x-card>
        <x-slot name="title">{{ __("Delete Site") }}</x-slot>
        <x-slot name="description">
            {{ __("Permanently delete the site from server") }}
        </x-slot>
        <p>
            {{ __("Once your site is deleted, all of its files will be deleted from the server.") }}
        </p>
        <div class="mt-5">
            <livewire:sites.delete-site :site="$site" />
        </div>
    </x-card>
</x-site-layout>
