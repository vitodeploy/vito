@props(["value" => 0])

<div class="bg-primary-200 dark:bg-primary-500 relative flex h-6 overflow-hidden rounded text-xs dark:bg-opacity-10">
    <div
        style="width: {{ $value }}%"
        class="bg-primary-500 flex flex-col justify-center whitespace-nowrap text-center text-white shadow-none transition-all duration-500 ease-out"
    ></div>
    <span
        class="{{ $value >= 40 ? "text-white" : "text-black dark:text-white" }} absolute left-0 right-0 top-1 text-center font-semibold"
    >
        {{ $value }}%
    </span>
</div>
