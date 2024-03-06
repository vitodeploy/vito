<x-modal name="restore-backup">
    <form
        id="restore-backup-form"
        :hx-post="restoreAction"
        x-init="$watch('restoreAction', () => htmx.process($el))"
        hx-swap="outerHTML"
        hx-select="#restore-backup-form"
        hx-ext="disable-element"
        hx-disable-element="#btn-restore"
        class="p-6"
    >
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __("Restore Backup") }}
        </h2>

        <div class="mt-6">
            <x-input-label for="database" :value="__('Database')" />
            <x-select-input id="database" name="database" class="mt-1 w-full">
                <option value="" selected disabled>{{ __("Select") }}</option>
                @foreach ($databases as $db)
                    <option value="{{ $db->id }}" @if(old('database') == $db->id) selected @endif>
                        {{ $db->name }}
                    </option>
                @endforeach
            </x-select-input>
            @error("database")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>

        <div class="mt-6 flex justify-end">
            <x-secondary-button type="button" x-on:click="$dispatch('close')">
                {{ __("Cancel") }}
            </x-secondary-button>

            <x-primary-button id="btn-restore" class="ml-3">
                {{ __("Restore") }}
            </x-primary-button>
        </div>
    </form>
</x-modal>
