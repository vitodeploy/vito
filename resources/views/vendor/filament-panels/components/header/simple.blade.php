@props([
    "heading" => null,
    "logo" => true,
    "subheading" => null,
])

<header class="fi-simple-header flex h-8 items-center justify-between">
    <div class="flex items-center gap-1">
        @if ($logo)
            <x-filament-panels::logo />
        @endif

        @if (filled($heading))
            <h1
                class="fi-simple-header-heading text-center text-2xl font-bold tracking-tight text-gray-950 dark:text-white"
            >
                {{ $heading }}
            </h1>
        @endif
    </div>

    @if (filled($subheading))
        <p class="fi-simple-header-subheading text-center text-sm text-gray-500 dark:text-gray-400">
            {{ $subheading }}
        </p>
    @endif
</header>
