@props(["open" => false, "align" => "right", "width" => "48", "contentClasses" => "list-none divide-y divide-gray-100 rounded-md border border-gray-100 bg-white py-1 text-base shadow dark:divide-gray-600 dark:border-gray-600 dark:bg-gray-700"])

@php
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
        class="{{ $width }} {{ $alignmentClasses }} absolute z-50 mt-2 rounded-md shadow-lg"
        style="display: none"
        @click="open = false"
    >
        <div class="{{ $contentClasses }} rounded-md ring-1 ring-black ring-opacity-5">
            {{ $content }}
        </div>
    </div>
</div>
