<div x-data="">
    <x-modal name="deployment-script">
        <form
            id="deployment-script-form"
            hx-post="{{ route("servers.sites.application.deployment-script", ["server" => $server, "site" => $site]) }}"
            hx-select="#deployment-script-form"
            hx-swap="outerHTML"
            class="p-6"
        >
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __("Deployment Script") }}
            </h2>

            <div class="mt-6">
                <x-input-label for="script" :value="__('Script')" />
                <x-textarea rows="10" id="script" name="script" class="mt-1 w-full">
                    {{ old("script", $site->deployment_script_text) }}
                </x-textarea>
                @error("script")
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
