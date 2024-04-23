<div class="cursor-pointer">
    <x-dropdown align="left">
        <x-slot:trigger>
            <div data-tooltip="Project">
                <div
                    class="flex h-10 w-max items-center rounded-md border border-gray-200 bg-gray-100 px-4 py-2 pr-7 text-sm text-gray-900 focus:ring-4 focus:ring-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:focus:ring-gray-600"
                >
                    {{ auth()->user()->currentProject?->name ?? __("Select Project") }}
                    <button type="button" class="absolute inset-y-0 right-0 flex items-center pr-2">
                        <x-heroicon name="o-chevron-down" class="h-4 w-4 text-gray-400" />
                    </button>
                </div>
            </div>
        </x-slot>
        <x-slot:content>
            @foreach (auth()->user()->projects as $project)
                <x-dropdown-link class="relative" :href="route('projects.switch', ['project' => $project])">
                    <span class="block truncate">{{ ucfirst($project->name) }}</span>
                    @if ($project->id == auth()->user()->current_project_id)
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-primary-600">
                            <x-heroicon name="o-check" class="h-5 w-5" />
                        </span>
                    @endif
                </x-dropdown-link>
            @endforeach

            <x-dropdown-link href="{{ route('projects') }}">
                {{ __("Projects List") }}
            </x-dropdown-link>
            <x-dropdown-link href="{{ route('projects', ['create' => 'open']) }}">
                {{ __("Create a Project") }}
            </x-dropdown-link>
        </x-slot>
    </x-dropdown>
</div>
