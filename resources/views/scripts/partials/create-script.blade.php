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
                @include("scripts.partials.fields.name", ["value" => old("name")])
            </div>

            <div class="mt-6">
                @include("scripts.partials.fields.content", ["value" => old("content")])
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
