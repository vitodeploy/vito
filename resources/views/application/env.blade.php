<div x-data="">
    <x-modal name="update-env">
        <form
            id="update-env-form"
            hx-post="{{ route("servers.sites.application.env", [$server, $site]) }}"
            hx-swap="outerHTML"
            hx-select="#update-env-form"
            class="p-6"
        >
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __("Update .env File") }}
            </h2>

            <div
                class="mt-6"
                id="env-content"
                hx-get="{{ route("servers.sites.application.env", [$server, $site]) }}"
                hx-trigger="load"
                hx-target="#env"
                hx-select="#env"
                hx-swap="outerHTML"
            >
                <x-input-label for="env" :value="__('.env')" />
                <x-textarea id="env" name="env" rows="10" class="mt-1 block w-full">
                    {{ old("env", session()->get("env") ?? "Loading...") }}
                </x-textarea>
                @error("env")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6 flex items-center justify-end">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    {{ __("Cancel") }}
                </x-secondary-button>

                <x-primary-button class="ml-3" hx-disable>
                    {{ __("Save") }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
