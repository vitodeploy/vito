<div class="mt-6">
    <label for="composer" class="inline-flex items-center">
        <input id="composer" wire:model.defer="inputs.composer" type="checkbox" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" name="composer">
        <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Run `composer install --no-dev`') }}</span>
    </label>
</div>
