<x-site-layout :site="$site">
    <x-slot name="pageTitle">{{ __("Application Logs") }}</x-slot>

    @include("site-logs-app.partials.change-default-log-path")

    @include("site-logs-app.partials.application-log-reader")
</x-site-layout>
