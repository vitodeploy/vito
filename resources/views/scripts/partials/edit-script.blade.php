<x-modal name="edit-script" :show="true">
    <form
        id="edit-script-form"
        hx-post="{{ route("scripts.edit", ["script" => $script]) }}"
        hx-swap="outerHTML"
        hx-select="#edit-script-form"
        class="p-6"
    >
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __("Edit script") }}
        </h2>

        <div class="mt-6">
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input
                value="{{ old('name', $script->name) }}"
                id="name"
                name="name"
                type="text"
                class="mt-1 w-full"
            />
            @error("name")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>

        <div class="mt-6">
            <x-input-label for="content" :value="__('Content')" />
            <x-textarea id="content" name="content" class="mt-1 min-h-[400px] w-full">
                {{ old("content", $script->content) }}
            </x-textarea>
            @error("content")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>

        <div class="mt-6 flex justify-end">
            <x-secondary-button type="button" x-on:click="$dispatch('close')">
                {{ __("Cancel") }}
            </x-secondary-button>

            <x-primary-button class="ml-3" hx-disable>
                {{ __("Save") }}
            </x-primary-button>
        </div>
    </form>
</x-modal>
