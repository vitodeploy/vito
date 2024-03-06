<x-card>
    <x-slot name="title">{{ __("Installing") }}</x-slot>
    <x-slot name="description">
        {{ __("The site is being installed") }}
    </x-slot>
    <div
        class="relative flex h-6 overflow-hidden rounded bg-primary-200 text-xs dark:bg-primary-500 dark:bg-opacity-10"
    >
        <div
            style="width: {{ $site->progress }}%"
            class="flex flex-col justify-center whitespace-nowrap bg-primary-500 text-center text-white shadow-none"
        ></div>
        <span
            class="{{ $site->progress >= 40 ? "text-white" : "text-black dark:text-white" }} absolute left-0 right-0 top-1 text-center font-semibold"
        >
            {{ $site->progress }}%
        </span>
    </div>
</x-card>
