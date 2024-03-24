<div class="mt-6">
    <label for="composer" class="inline-flex items-center">
        <input
            id="composer"
            type="checkbox"
            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
            name="composer"
            @if (old("composer") === "on") checked @endif
        />
        <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">
            {{ __("Run `composer install --no-dev`") }}
        </span>
    </label>
</div>
