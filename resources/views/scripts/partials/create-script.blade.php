<div>
    <x-primary-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'create-script')">
        {{ __("Create Script") }}
    </x-primary-button>

    <x-modal name="create-script">
        <form
            id="create-script-form"
            hx-post="{{ route("scripts.store") }}"
            hx-swap="outerHTML"
            hx-select="#create-script-form"
            class="p-6"
        >
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __("Create script") }}
            </h2>

            <div class="mt-6">
                <x-input-label for="name" :value="__('Name')" />
                <x-text-input value="{{ old('name') }}" id="name" name="name" type="text" class="mt-1 w-full" />
                @error("name")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6">
                <x-input-label for="content" :value="__('Content')" />
                <x-textarea id="content" name="content" class="mt-1 min-h-[400px] w-full">
                    {{ old("content") }}
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
                    {{ __("Create") }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
