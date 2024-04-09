<x-simple-card>
    <div class="mb-5 flex items-center justify-between">
        <x-title>{{ __("General Info") }}</x-title>
        <x-icon-button :href="route('monitors.show', ['monitor' => $monitor])">
            <x-heroicon-o-pencil class="h-4 w-4" />
        </x-icon-button>
    </div>
    <div class="flex items-center justify-between">
        <div class="font-bold">{{ __("Name") }}</div>
        <div>{{ $monitor->name }}</div>
    </div>
    <x-hr />
    @if (isset($monitor->type_data["address"]))
        <div class="flex items-center justify-between">
            <div class="font-bold">{{ __("Address") }}</div>
            <div class="max-w-[200px] overflow-hidden text-ellipsis">{{ $monitor->type_data["address"] }}</div>
        </div>
    @endif

    <x-hr />
    <div class="flex items-center justify-between">
        <div class="font-bold">{{ __("Locations") }}</div>
        <div class="uppercase">{{ implode(", ", $monitor->locations) }}</div>
    </div>
    <x-hr />
    <div class="flex items-center justify-between">
        <div class="font-bold">{{ __("Check Fequency") }}</div>
        <div>{{ __("Every :s", ["s" => $monitor->check_frequency]) }}</div>
    </div>
    <x-hr />
    @isset($monitor->type_data["threshold"])
        <div class="flex items-center justify-between">
            <div class="font-bold">{{ __("Alerting Threshold") }}</div>
            <div>{{ __("After") }} {{ $monitor->type_data["threshold"] }}</div>
        </div>
        <x-hr />
    @endisset

    <div class="flex items-center justify-between">
        <div class="font-bold">{{ __("Created At") }}</div>
        <x-datetime :value="$monitor->created_at" />
    </div>
    <x-hr />
    <div class="flex items-center justify-between">
        <div class="font-bold">{{ __("Status") }}</div>
        <div>
            @include("monitors.partials.status", ["monitor" => $monitor])
        </div>
    </div>
</x-simple-card>
