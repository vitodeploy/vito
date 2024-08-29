<x-modal
    name="edit-tag"
    :show="true"
    x-on:modal-edit-tag-closed.window="window.history.pushState('', '', '{{ route('settings.tags') }}');"
>
    <form
        id="edit-tag-form"
        hx-post="{{ route("settings.tags.update", ["tag" => $tag->id]) }}"
        hx-swap="outerHTML"
        hx-select="#edit-tag-form"
        hx-ext="disable-element"
        hx-disable-element="#btn-edit-tag"
        class="p-6"
    >
        @csrf

        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __("Edit Tag") }}
        </h2>

        <div class="mt-6">
            @include("settings.tags.fields.name", ["value" => old("name", $tag->name)])
        </div>

        <div class="mt-6">
            @include("settings.tags.fields.color", ["value" => old("color", $tag->color)])
        </div>

        <div class="mt-6 flex justify-end">
            <x-secondary-button type="button" x-on:click="$dispatch('close')">
                {{ __("Cancel") }}
            </x-secondary-button>

            <x-primary-button id="btn-edit-tag" class="ml-3">
                {{ __("Save") }}
            </x-primary-button>
        </div>
    </form>
</x-modal>
