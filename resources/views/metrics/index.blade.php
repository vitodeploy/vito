<x-server-layout :server="$server">
    <x-slot name="pageTitle">{{ $server->name }} - Metrics</x-slot>

    @include("metrics.partials.filter")

    @php
        $cpuSets = [
            "name" => "CPU Load",
            "data" => $data["metrics"]->pluck("load")->toArray(),
            "color" => "#ff9900",
        ];
        $memorySets = [
            "name" => "Memory Usage",
            "data" => $data["metrics"]->pluck("memory_used")->toArray(),
            "color" => "#3366cc",
        ];
        $diskSets = [
            "name" => "Disk Usage",
            "data" => $data["metrics"]->pluck("disk_used")->toArray(),
            "color" => "#109618",
        ];
    @endphp

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <x-chart
            id="cpu-load"
            type="area"
            title="CPU Load"
            :sets="[$cpuSets]"
            :categories="$data['metrics']->pluck('date')->toArray()"
            color="#ff9900"
            class="h-[200px] !p-0"
        />

        <x-chart
            id="memory-usage"
            type="area"
            title="Memory"
            :sets="[$memorySets]"
            :categories="$data['metrics']->pluck('date')->toArray()"
            color="#3366cc"
            class="h-[200px] !p-0"
        />

        <x-chart
            id="disk-usage"
            type="area"
            title="Disk"
            :sets="[$diskSets]"
            :categories="$data['metrics']->pluck('date')->toArray()"
            color="#109618"
            class="h-[200px] !p-0"
        />
    </div>

    <div class="mt-10">
        <x-chart
            id="resource-usage"
            type="line"
            title="Resource Usage"
            :sets="[$cpuSets, $memorySets, $diskSets]"
            :categories="$data['metrics']->pluck('date')->toArray()"
            color="#109618"
            :toolbar="true"
            class="h-[400px] !px-0 !pt-0"
        />
    </div>
</x-server-layout>
