<x-server-layout :server="$server">
    <x-slot name="pageTitle">{{ $server->name }} - Metrics</x-slot>

    <div class="space-y-4">
        <div class="flex items-center justify-between">
            @include("metrics.partials.filter")
            @include("metrics.partials.data-retention")
        </div>
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
                formatter="function (value) { return (value / 1000).toFixed(2) + ` MB`; }"
                class="h-[200px] !p-0"
            />

            <x-chart
                id="disk-usage"
                type="area"
                title="Disk"
                :sets="[$diskSets]"
                :categories="$data['metrics']->pluck('date')->toArray()"
                formatter="function (value) { return value + ` MB`; }"
                color="#109618"
                class="h-[200px] !p-0"
            />
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="grid grid-cols-1 gap-4">
                <x-simple-card class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <span class="text-center lg:text-left">Total Memory</span>
                    <div class="text-center text-xl font-bold text-gray-600 dark:text-gray-400 lg:text-right">
                        {{ $lastMetric ? number_format((int) ($lastMetric->memory_total / 1024)) : "-" }} MB
                    </div>
                </x-simple-card>
                <x-simple-card class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <span class="text-center lg:text-left">Used Memory</span>
                    <div class="text-center text-xl font-bold text-gray-600 dark:text-gray-400 lg:text-right">
                        {{ $lastMetric ? number_format((int) ($lastMetric->memory_used / 1024)) : "-" }} MB
                    </div>
                </x-simple-card>
                <x-simple-card class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <span class="text-center lg:text-left">Free Memory</span>
                    <div class="text-center text-xl font-bold text-gray-600 dark:text-gray-400 lg:text-right">
                        {{ $lastMetric ? number_format((int) ($lastMetric->memory_free / 1024)) : "-" }} MB
                    </div>
                </x-simple-card>
            </div>
            <div class="grid grid-cols-1 gap-4">
                <x-simple-card class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <span class="text-center lg:text-left">Total Space</span>
                    <div class="text-center text-xl font-bold text-gray-600 dark:text-gray-400 lg:text-right">
                        {{ $lastMetric ? number_format($lastMetric->disk_total) : "-" }} MB
                    </div>
                </x-simple-card>
                <x-simple-card class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <span class="text-center lg:text-left">Used Space</span>
                    <div class="text-center text-xl font-bold text-gray-600 dark:text-gray-400 lg:text-right">
                        {{ $lastMetric ? number_format($lastMetric->disk_used) : "-" }} MB
                    </div>
                </x-simple-card>
                <x-simple-card class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <span class="text-center lg:text-left">Free Space</span>
                    <div class="text-center text-xl font-bold text-gray-600 dark:text-gray-400 lg:text-right">
                        {{ $lastMetric ? number_format($lastMetric->disk_free) : "-" }} MB
                    </div>
                </x-simple-card>
            </div>
        </div>
    </div>

    @stack("modals")
</x-server-layout>
