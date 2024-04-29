<div>
    <x-icon-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'edit-project-{{ $project->id }}')">
        <x-heroicon name="o-pencil" class="h-5 w-5" />
    </x-icon-button>

    <x-modal name="edit-project-{{ $project->id }}">
        <form
            id="edit-project-form-{{ $project->id }}"
            hx-post="{{ route("settings.projects.update", $project) }}"
            hx-swap="outerHTML"
            hx-select="#edit-project-form-{{ $project->id }}"
            hx-ext="disable-element"
            hx-disable-element="#btn-edit-project-{{ $project->id }}"
            class="p-6 text-left"
        >
            @csrf

            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __("Edit Project") }}
            </h2>

            <div class="mt-6">
                <x-input-label for="edit-name-{{ $project->id }}" value="Name" />
                <x-text-input
                    value="{{ old('name', $project->name) }}"
                    id="edit-name-{{ $project->id }}"
                    name="name"
                    type="text"
                    class="mt-1 w-full"
                />
                @error("name")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    {{ __("Cancel") }}
                </x-secondary-button>

                <x-primary-button id="btn-edit-project-{{ $project->id }}" class="ml-3">
                    {{ __("Save") }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
