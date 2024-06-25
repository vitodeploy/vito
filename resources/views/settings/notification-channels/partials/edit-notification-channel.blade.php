<x-modal
    name="edit-notification-channel"
    :show="true"
    x-on:modal-edit-notification-channel-closed.window="window.history.pushState('', '', '{{ route('settings.notification-channels') }}');"
>
    <form
        id="edit-notification-channel-form"
        hx-post="{{ route("settings.notification-channels.update", ["notificationChannel" => $notificationChannel->id]) }}"
        hx-swap="outerHTML"
        hx-select="#edit-notification-channel-form"
        hx-ext="disable-element"
        hx-disable-element="#btn-edit-notification-channel"
        class="p-6"
    >
        @csrf

        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __("Edit Channel") }}
        </h2>

        <div class="mt-6">
            <x-input-label for="edit-label" value="Label" />
            <x-text-input
                value="{{ $notificationChannel->label }}"
                id="edit-label"
                name="label"
                type="text"
                class="mt-1 w-full"
            />
            @error("label")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>

        <div class="mt-6">
            <x-checkbox
                id="edit-global"
                name="global"
                :checked="old('global', $notificationChannel->project_id === null ? 1 : null)"
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

            <x-primary-button id="btn-edit-notification-channel" class="ml-3">
                {{ __("Save") }}
            </x-primary-button>
        </div>
    </form>
</x-modal>
