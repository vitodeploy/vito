<x-site-layout :site="$site">
    <x-slot name="pageTitle">{{ __("Application Logs") }}</x-slot>

    @include("site-logs-app.partials.update-default-log-path")

    @include("site-logs-app.partials.application-log-viewer")
</x-site-layout>
