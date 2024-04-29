<div>
    <x-primary-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'create-project')">
        {{ __("Create") }}
    </x-primary-button>

    <x-modal name="create-project" :show="request()->has('create')">
        <form
            id="create-project-form"
            hx-post="{{ route("settings.projects.create") }}"
            hx-swap="outerHTML"
            hx-select="#create-project-form"
            hx-ext="disable-element"
            hx-disable-element="#btn-create-project"
            class="p-6"
        >
            @csrf
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __("Create Project") }}
            </h2>

            <div class="mt-6">
                <x-input-label for="name" value="Name" />
                <x-text-input value="{{ old('name') }}" id="name" name="name" type="text" class="mt-1 w-full" />
                @error("name")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    {{ __("Cancel") }}
                </x-secondary-button>

                <x-primary-button id="btn-create-project" class="ml-3">
                    {{ __("Create") }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
