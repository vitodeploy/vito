<x-modal
    name="edit-server-provider"
    :show="true"
    x-on:modal-edit-server-provider-closed.window="window.history.pushState('', '', '{{ route('settings.server-providers') }}');"
>
    <form
        id="edit-server-provider-form"
        hx-post="{{ route("settings.server-providers.update", ["serverProvider" => $serverProvider->id]) }}"
        hx-swap="outerHTML"
        hx-select="#edit-server-provider-form"
        hx-ext="disable-element"
        hx-disable-element="#btn-edit-server-provider"
        class="p-6"
    >
        @csrf

        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __("Edit Provider") }}
        </h2>

        <div class="mt-6">
            <x-input-label for="edit-name" value="Name" />
            <x-text-input
                value="{{ $serverProvider->profile }}"
                id="edit-name"
                name="name"
                type="text"
                class="mt-1 w-full"
            />
            @error("name")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>

        <div class="mt-6">
            <x-checkbox
                id="edit-global"
                name="global"
                :checked="old('global', $serverProvider->project_id === null ? 1 : null)"
                value="1"
            >
                Is Global (Accessible in all projects)
            </x-checkbox>
            @error("global")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>

        <div class="mt-6 flex justify-end">
            <x-secondary-button type="button" x-on:click="$dispatch('close')">
                {{ __("Cancel") }}
            </x-secondary-button>

            <x-primary-button id="btn-edit-server-provider" class="ml-3">
                {{ __("Save") }}
            </x-primary-button>
        </div>
    </form>
</x-modal>
