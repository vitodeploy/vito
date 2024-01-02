<div x-data="projectCombobox()">
    <div class="relative">
        <div @click="open = !open" class="z-0 w-full cursor-pointer px-4 py-3 pr-10 text-md leading-5 text-gray-100 focus:ring-1 focus:ring-gray-700 bg-gray-900 rounded-md h-10 flex items-center" x-text="selected.name ?? 'Select Project'"></div>
        <button type="button" @click="open = !open" class="z-0 absolute inset-y-0 right-0 flex items-center pr-2">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-5 w-5 text-gray-400"><path fill-rule="evenodd" d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z" clip-rule="evenodd"></path></svg>
        </button>
        <div
            x-show="open"
            @click.away="open = false"
            class="z-10 absolute mt-1 w-full overflow-auto rounded-md pb-1 bg-white dark:bg-gray-700 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
            <div class="p-2 relative">
                <input x-model="query"
                       @input="filterProjectsAndOpen"
                       placeholder="Filter"
                       class="w-full py-2 pl-3 pr-10 text-sm leading-5 dark:text-gray-100 focus:ring-1 focus:ring-gray-400 dark:focus:ring-800 bg-gray-200 dark:bg-gray-900 rounded-md"
                >
            </div>
            <div class="relative max-h-[350px] overflow-y-auto">
                <template x-for="(project, index) in filteredProjects" :key="index">
                    <div
                        @click="selectProject(project); open = false"
                        :class="project.id === selected.id ? 'cursor-default bg-primary-600 text-white' : 'cursor-pointer'"
                        class="relative select-none py-2 px-4 text-gray-700 dark:text-white hover:bg-primary-600 hover:text-white">
                        <span class="block truncate" x-text="project.name"></span>
                        <template x-if="project.id === selected.id">
                            <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-5 w-5"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"></path></svg>
                            </span>
                        </template>
                    </div>
                </template>
            </div>
            <div
                x-show="filteredProjects.length === 0"
                class="relative cursor-default select-none py-2 px-4 text-gray-700 dark:text-white block truncate">
                No projects found!
            </div>
            <div class="py-1">
                <hr class="border-gray-300 dark:border-gray-600">
            </div>
            <div>
                <a
                    href="{{ route('projects') }}"
                    class="relative select-none py-2 px-4 text-gray-700 dark:text-white hover:bg-primary-600 hover:text-white block cursor-pointer">
                    <span class="block truncate">Projects List</span>
                </a>
            </div>
            <div>
                <a
                    href="{{ route('projects', ['create' => true]) }}"
                    class="relative select-none py-2 px-4 text-gray-700 dark:text-white hover:bg-primary-600 hover:text-white block cursor-pointer">
                    <span class="block truncate">Create a Project</span>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    function projectCombobox() {
        const projects = @json(auth()->user()->projects()->select('id', 'name')->get());
        return {
            open: false,
            query: '',
            projects: projects,
            selected: @if(isset($project)) @json($project->only('id', 'name')) @else {} @endif,
            filteredProjects: projects,
            selectProject(project) {
                if (this.selected.id !== project.id) {
                    this.selected = project;
                    window.location.href = '{{ url('/settings/projects/') }}/' + project.id
                }
            },
            filterProjectsAndOpen() {
                if (this.query === '') {
                    this.filteredProjects = this.projects;
                    this.open = false;
                } else {
                    this.filteredProjects = this.projects.filter((project) =>
                        project.name
                            .toLowerCase()
                            .replace(/\s+/g, '')
                            .includes(this.query.toLowerCase().replace(/\s+/g, ''))
                    );
                    this.open = true;
                }
            },
        };
    }
</script>
