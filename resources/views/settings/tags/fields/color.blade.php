@php($id = "color-" . uniqid())
<div x-data="{
    value: '{{ $value }}',
}">
    <x-input-label for="color" :value="__('Color')" />
    <input x-bind:value="value" id="{{ $id }}" name="color" type="hidden" />
    <x-dropdown class="relative" align="left">
        <x-slot name="trigger">
            <x-dropdown-trigger>
                <div class="flex items-center">
                    <div x-show="value" x-bind:class="`bg-${value}-500 mr-1 h-3 w-3 rounded-full`"></div>
                    <span x-text="value || 'Select a color'"></span>
                </div>
            </x-dropdown-trigger>
        </x-slot>

        <x-slot name="content">
            <div class="z-50 max-h-[200px] overflow-y-auto">
                @foreach (config("core.tag_colors") as $color)
                    <x-dropdown-link href="#" x-on:click="value = '{{ $color }}'" class="flex items-center capitalize">
                        <div class="bg-{{ $color }}-500 mr-1 h-3 w-3 rounded-full"></div>
                        {{ $color }}
                    </x-dropdown-link>
                @endforeach
            </div>
        </x-slot>
    </x-dropdown>
    @error("color")
        <x-input-error class="mt-2" :messages="$message" />
    @enderror
</div>
