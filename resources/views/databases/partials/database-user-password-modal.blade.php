<x-modal name="database-user-password">
    <div id="database-user-password-content" class="p-6">
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __("View Password") }}
        </h2>

        <div class="mt-6">
            <x-input-label :value="__('Password')" />
            <x-text-input
                id="txt-database-user-password"
                type="text"
                class="mt-1 w-full"
                disabled
                value="{{ session()->has('password') ? session()->get('password') : '' }}"
            />
        </div>

        <div class="mt-6 flex justify-end">
            <x-secondary-button type="button" x-on:click="$dispatch('close')">
                {{ __("Close") }}
            </x-secondary-button>

            <x-primary-button x-data="{ copied: false }" class="ml-2">
                <div x-show="copied" class="flex items-center">
                    {{ __("Copied") }}
                </div>
                <div
                    x-show="!copied"
                    x-on:click="
                        window.copyToClipboard(
                            '{{ session()->has("password") ? session()->get("password") : "" }}',
                        )
                        copied = true
                        setTimeout(() => {
                            copied = false
                        }, 2000)
                    "
                >
                    {{ __("Copy") }}
                </div>
            </x-primary-button>
        </div>
    </div>
</x-modal>
