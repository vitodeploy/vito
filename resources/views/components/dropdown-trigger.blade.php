<div>
    <div
        class="block w-full cursor-pointer rounded-md border border-gray-300 p-2.5 text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-primary-600 dark:focus:ring-primary-600"
    >
        {{ $slot }}
    </div>
    <button type="button" class="absolute inset-y-0 right-0 flex items-center pr-2">
        <x-heroicon name="o-chevron-down" class="h-4 w-4 text-gray-400" />
    </button>
</div>
