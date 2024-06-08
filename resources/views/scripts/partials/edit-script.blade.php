<x-modal
    name="edit-script"
    :show="true"
    x-on:modal-edit-script-closed.window="window.history.pushState('', '', '{{ route('scripts.index') }}');"
>
    <form
        id="edit-script-form"
        hx-post="{{ route("scripts.edit", ["script" => $script]) }}"
        hx-swap="outerHTML"
        hx-select="#edit-script-form"
        class="p-6"
    >
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __("Edit script") }}
        </h2>

        <div class="mt-6">
            @include("scripts.partials.fields.name", ["value" => old("name", $script->name)])
        </div>

        <div class="mt-6">
            @include("scripts.partials.fields.content", ["value" => old("content", $script->content)])
        </div>

        <div class="mt-6 flex justify-end">
            <x-secondary-button type="button" x-on:click="$dispatch('close')">
                {{ __("Cancel") }}
            </x-secondary-button>

            <x-primary-button class="ml-3" hx-disable>
                {{ __("Save") }}
            </x-primary-button>
        </div>
    </form>
</x-modal>
