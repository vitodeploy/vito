<x-modal name="link-database-user">
    <form wire:submit.prevent="link" class="p-6">
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Link User to Databases') }}
        </h2>

        <div class="mt-6">
            @foreach($databases as $database)
                <div class="mb-2">
                    <label for="db-{{ $database->id }}" class="inline-flex items-center">
                        <input id="db-{{ $database->id }}" wire:model.defer="link" value="{{ $database->name }}" type="checkbox" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" name="link">
                        <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">{{ $database->name }}</span>
                    </label>
                </div>
            @endforeach
        </div>

        <div class="mt-6 flex justify-end">
            <x-secondary-button type="button" x-on:click="$dispatch('close')">
                {{ __('Close') }}
            </x-secondary-button>

            <x-primary-button class="ml-2" @linked.window="$dispatch('close')">
                {{ __('Save') }}
            </x-primary-button>
        </div>
    </form>
</x-modal>
