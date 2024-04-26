<x-card>
    <x-slot name="title">Projects</x-slot>

    <x-slot name="description">Manage the projects that the user is in</x-slot>

    <form
        id="update-projects"
        hx-post="{{ route("admin.users.update-projects", ["user" => $user]) }}"
        hx-swap="outerHTML"
        hx-select="#update-projects"
        hx-trigger="submit"
        hx-ext="disable-element"
        hx-disable-element="#btn-save-projects"
        class="mt-6 space-y-6"
    >
        @csrf

        <div>
            <x-input-label value="Current Projects" />

            <div
                class="mt-1"
                x-data="{
                    removeProject(id) {
                        document.getElementById(`project-${id}`).remove()
                    },
                }"
            >
                @foreach ($user->projects as $project)
                    <div id="project-{{ $project->id }}" class="mr-1 inline-flex">
                        <x-status status="info" class="flex items-center">
                            {{ $project->name }}
                            <x-heroicon
                                name="o-x-mark"
                                class="ml-1 h-4 w-4 cursor-pointer"
                                x-on:click="removeProject({{ $project->id }})"
                            />
                            <input type="hidden" name="projects[]" value="{{ $project->id }}" />
                        </x-status>
                    </div>
                @endforeach
            </div>
        </div>

        <div>
            <x-input-label value="Add new Project" />

            <x-select-input id="project" class="mt-1 w-full" x-model="project">
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                @endforeach
            </x-select-input>
        </div>
    </form>

    <x-slot name="actions">
        <x-primary-button id="btn-save-projects" form="update-projects">Save</x-primary-button>
    </x-slot>
</x-card>
