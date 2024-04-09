<x-server-layout :server="$server">
    <x-slot name="pageTitle">{{ $server->name }} - Metrics</x-slot>

    @include("metrics.partials.filter")

    @include("metrics.partials.metrics")

    {{--
        @include(
        "metrics.partials.metrics",
        [
        "id" => "load-chart",
        "title" => "CPU Usage",
        "field" => "load",
        "color" => "#4F45E4",
        ]
        )
        
        @include(
        "metrics.partials.metrics",
        [
        "id" => "memory-chart",
        "title" => "Memory Usage",
        "field" => "memory_used",
        "color" => "#4F45E4",
        ]
        )
        
        @include(
        "metrics.partials.metrics",
        [
        "id" => "disk-chart",
        "title" => "Disk Usage",
        "field" => "disk_used",
        "color" => "#4F45E4",
        ]
        )
    --}}
</x-server-layout>
