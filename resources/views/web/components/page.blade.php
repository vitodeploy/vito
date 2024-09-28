<div {{ $this->getExtraAttributesBag() }}>
    <x-filament-panels::page>
        @if (method_exists($this, "getSecondSubNavigation"))
            <x-filament-panels::page.sub-navigation.tabs :navigation="$this->getSecondSubNavigation()" />
        @endif

        @foreach ($this->getWidgets() as $key => $widget)
            @livewire($widget[0], $widget[1] ?? [], key(class_basename($widget[0]) . "-" . $key))
        @endforeach
    </x-filament-panels::page>
</div>
