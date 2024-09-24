<x-filament-panels::page wire:poll.5s="dispatch('$refresh')">
    @foreach ($this->getWidgets() as $key => $widget)
        @livewire($widget[0], $widget[1] ?? [], key(class_basename($widget[0]) . '-' . $key))
    @endforeach
</x-filament-panels::page>
