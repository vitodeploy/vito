<x-site-layout :site="$site">
    <x-slot name="pageTitle">{{ __("Settings") }}</x-slot>

    @include("site-settings.partials.site-details")

    @include("site-settings.partials.change-php-version")

    @include("site-settings.partials.update-aliases")

    @if ($site->source_control_id)
        @include("site-settings.partials.update-source-control")
    @endif

    @include("site-settings.partials.update-v-host")

    <x-card>
        <x-slot name="title">{{ __("Delete Site") }}</x-slot>
        <x-slot name="description">
            {{ __("Permanently delete the site from server") }}
        </x-slot>
        <p>
            {{ __("Once your site is deleted, all of its files will be deleted from the server.") }}
        </p>
        <div class="mt-5">
            @include("site-settings.partials.delete-site")
        </div>
    </x-card>
</x-site-layout>
