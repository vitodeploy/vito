<x-modal name="update-php-ini-{{ $type }}">
    <form
        id="update-php-ini-{{ $type }}-form"
        hx-post="{{ route("servers.php.update-ini", ["server" => $server]) }}"
        hx-swap="outerHTML"
        hx-select="#update-php-ini-{{ $type }}-form"
        class="p-6"
    >
        <input type="hidden" name="type" value="{{ $type }}" />
        <input type="hidden" name="version" :value="version" />

        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __("Update php.ini (:type)", ["type" => $type]) }}
        </h2>

        <div class="mt-6">
            <x-input-label for="ini" value="php.ini" />
            @php($ini = old("ini", session()->get("ini") ?? "Loading..."))
            <x-editor id="ini" name="ini" lang="ini" :value="$ini" />
            @error("ini")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror

            @error("version")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>

        <div class="mt-6 flex justify-end">
            <x-secondary-button type="button" x-on:click="$dispatch('close')">
                {{ __("Cancel") }}
            </x-secondary-button>

            <x-primary-button hx-disable class="ml-3">
                {{ __("Save") }}
            </x-primary-button>
        </div>
    </form>
</x-modal>
