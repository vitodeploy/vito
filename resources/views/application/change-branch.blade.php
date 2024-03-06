<div x-data="">
    <x-modal name="change-branch">
        <form
            id="change-branch-form"
            hx-post="{{ route("servers.sites.application.branch", ["server" => $server, "site" => $site]) }}"
            hx-select="#change-branch-form"
            hx-swap="outerHTML"
            class="p-6"
        >
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __("Change Branch") }}
            </h2>

            <div class="mt-6">
                <x-input-label for="branch" :value="__('Branch')" />
                <x-text-input
                    value="{{ old('branch', $site->branch) }}"
                    id="branch"
                    name="branch"
                    type="text"
                    class="mt-1 w-full"
                />
                @error("branch")
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
