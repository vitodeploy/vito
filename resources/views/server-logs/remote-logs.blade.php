<x-server-layout :server="$server">
    @include("server-logs.partials.header")

    @include("server-logs.partials.add-log")
    @include("server-logs.partials.logs-list-live")
</x-server-layout>
