<div {{ $this->getExtraAttributesBag() }}>
    <x-filament-panels::page>
        @if (method_exists($this, "getSecondSubNavigation") && count($this->getSecondSubNavigation()) > 0)
            <x-filament-panels::page.sub-navigation.tabs class="!flex" :navigation="$this->getSecondSubNavigation()" />
        @endif

        @foreach ($this->getWidgets() as $key => $widget)
            @livewire($widget[0], $widget[1] ?? [], key(class_basename($widget[0]) . "-" . $key))
        @endforeach
    </x-filament-panels::page>
</div>
