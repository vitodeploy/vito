<x-modal name="link-database-user">
    <div id="link-database-user-modal">
        <form
            id="link-database-user-form"
            :hx-post="linkAction"
            x-init="$watch('linkAction', () => htmx.process($el))"
            hx-swap="outerHTML"
            hx-select="#link-database-user-form"
            hx-ext="disable-element"
            hx-disable-element="#btn-link-database-user"
            class="p-6"
        >
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __("Link User to Databases") }}
            </h2>

            <div class="mt-6">
                @foreach ($databases as $database)
                    <div class="mb-2">
                        <label for="db-{{ $database->id }}" class="inline-flex items-center">
                            <input
                                id="db-{{ $database->id }}"
                                value="{{ $database->name }}"
                                x-model="linkedDatabases"
                                type="checkbox"
                                name="databases[]"
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                            />
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                                {{ $database->name }}
                            </span>
                        </label>
                    </div>
                @endforeach

                @error("databases")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    {{ __("Close") }}
                </x-secondary-button>

                <x-primary-button id="btn-link-database-user" class="ml-2">
                    {{ __("Save") }}
                </x-primary-button>
            </div>
        </form>
    </div>
</x-modal>
