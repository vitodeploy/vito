<div>
    <x-primary-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'create-tag')">
        {{ __("Create Tag") }}
    </x-primary-button>

    <x-modal name="create-tag">
        <form
            id="create-tag-form"
            hx-post="{{ route("settings.tags.create") }}"
            hx-swap="outerHTML"
            hx-select="#create-tag-form"
            hx-ext="disable-element"
            hx-disable-element="#btn-create-tag"
            class="p-6"
            x-data="{}"
        >
            @csrf
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __("Create Tag") }}
            </h2>

            <div class="mt-6">
                @include("settings.tags.fields.name", ["value" => old("name")])
            </div>

            <div class="mt-6">
                @include("settings.tags.fields.color", ["value" => old("color")])
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    {{ __("Cancel") }}
                </x-secondary-button>

                <x-primary-button id="btn-create-tag" class="ml-3">
                    {{ __("Save") }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
