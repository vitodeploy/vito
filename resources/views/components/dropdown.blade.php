@props([
    "open" => false,
    "align" => "right",
    "width" => "48",
    "contentClasses" => "list-none divide-y divide-gray-100 rounded-md bg-white text-base dark:divide-gray-600 dark:bg-gray-700",
    "search" => false,
    "searchUrl" => "",
    "hideIfEmpty" => false,
    "closeOnClick" => true,
])

@php
    if (! $hideIfEmpty) {
        $contentClasses .= " py-1 border border-gray-200 dark:border-gray-600";
    }

    switch ($align) {
        case "left":
            $alignmentClasses = "left-0 origin-top-left";
            break;
        case "top":
            $alignmentClasses = "origin-top";
            break;
        case "right":
        default:
            $alignmentClasses = "right-0 origin-top-right";
            break;
    }

    switch ($width) {
        case "48":
            $width = "w-48";
            break;
        case "56":
            $width = "w-56";
            break;
        case "full":
            $width = "w-full";
            break;
    }
@endphp

<div class="relative" x-data="{ open: @js($open) }" @click.outside="open = false" @close.stop="open = false">
    <div @click="open = ! open">
        {{ $trigger }}
    </div>

    <div
        x-show="open"
        x-transition:enter="transition duration-200 ease-out"
        x-transition:enter-start="scale-95 transform opacity-0"
        x-transition:enter-end="scale-100 transform opacity-100"
        x-transition:leave="transition duration-75 ease-in"
        x-transition:leave-start="scale-100 transform opacity-100"
        x-transition:leave-end="scale-95 transform opacity-0"
        class="{{ $width }} {{ $alignmentClasses }} absolute z-50 mt-2 rounded-md"
        style="display: none"
        @if ($closeOnClick) @click="open = false" @endif
    >
        <div class="{{ $contentClasses }} rounded-md">
            {{ $content }}
        </div>
    </div>
</div>
