<x-filament-panels::page>
    @foreach ($this->getWidgets() as $widget)
        @php(ds($widget))
        @livewire($widget[0], $widget[1] ?? [])
    @endforeach
</x-filament-panels::page>
