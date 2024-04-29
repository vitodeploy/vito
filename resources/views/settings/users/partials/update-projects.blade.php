<x-card>
    <x-slot name="title">Projects</x-slot>

    <x-slot name="description">Manage the projects that the user is in</x-slot>

    <x-slot name="aside">
        <x-secondary-button :href="route('settings.users.index')">Back to Users</x-secondary-button>
    </x-slot>

    <form
        id="update-projects"
        hx-post="{{ route("settings.users.update-projects", ["user" => $user]) }}"
        hx-swap="outerHTML"
        hx-select="#update-projects"
        hx-trigger="submit"
        hx-ext="disable-element"
        hx-disable-element="#btn-save-projects"
        class="mt-6"
    >
        @csrf

        <script>
            let projects = @json($user->projects);
        </script>

        <div
            class="space-y-6"
            x-data="{
                q: '',
                projects: projects,
                search() {
                    htmx.ajax('GET', '{{ request()->getUri() }}?q=' + this.q, {
                        target: '#projects-list',
                        swap: 'outerHTML',
                        select: '#projects-list',
                    }).then(() => {
                        document.getElementById('q').focus()
                    })
                },
                addProject(project) {
                    if (this.projects.find((p) => p.id === project.id)) {
                        return
                    }

                    this.projects.push(project)
                    this.q = ''
                },
                removeProject(id) {
                    this.projects = this.projects.filter((project) => project.id !== id)
                },
            }"
        >
            <div>
                <x-input-label value="Projects" />

                <div class="mt-1">
                    <template x-for="project in projects">
                        <div class="mr-1 inline-flex">
                            <x-status status="info" class="flex items-center">
                                <span x-text="project.name"></span>
                                <x-heroicon
                                    name="o-x-mark"
                                    class="ml-1 h-4 w-4 cursor-pointer"
                                    x-on:click="removeProject(project.id)"
                                />
                                <input type="hidden" name="projects[]" x-bind:value="project.id" />
                            </x-status>
                        </div>
                    </template>
                </div>
            </div>

            <div>
                <x-input-label value="Add new Project" />

                @php
                    $projects = \App\Models\Project::query()
                        ->where(function ($query) {
                            if (request()->has("q")) {
                                $query->where("name", "like", "%" . request("q") . "%");
                            }
                        })
                        ->take(5)
                        ->get();
                @endphp

                <x-dropdown width="full">
                    <x-slot name="trigger">
                        <x-text-input
                            id="q"
                            name="q"
                            x-model="q"
                            type="text"
                            class="mt-1 w-full"
                            placeholder="Search for projects..."
                            autocomplete="off"
                            x-on:input.debounce.500ms="search"
                        />
                    </x-slot>
                    <x-slot name="content">
                        <div id="projects-list">
                            @foreach ($projects as $project)
                                <x-dropdown-link
                                    class="cursor-pointer"
                                    x-on:click="addProject({ id: {{ $project->id }}, name: '{{ $project->name }}' })"
                                >
                                    {{ $project->name }}
                                </x-dropdown-link>
                            @endforeach
                        </div>
                    </x-slot>
                </x-dropdown>
            </div>
        </div>
    </form>

    <x-slot name="actions">
        <x-primary-button id="btn-save-projects" form="update-projects">Save</x-primary-button>
    </x-slot>
</x-card>
