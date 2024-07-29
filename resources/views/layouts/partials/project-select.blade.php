<div class="cursor-pointer">
    <x-dropdown align="left">
        <x-slot:trigger>
            <div data-tooltip="Project">
                <div
                    class="flex h-10 w-max items-center rounded-md border border-gray-200 bg-gray-100 px-4 py-2 pr-7 text-sm text-gray-900 focus:ring-4 focus:ring-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:focus:ring-gray-600"
                >
                    <x-heroicon name="o-inbox-stack" class="mr-2 h-4 w-4 lg:hidden" />
                    <span class="hidden lg:block">
                        @if (auth()->user()->currentProject &&auth()->user()->can("view", auth()->user()->currentProject))
                            {{ auth()->user()->currentProject->name }}
                        @else
                            {{ __("Select Project") }}
                        @endif
                    </span>
                    <button type="button" class="absolute inset-y-0 right-0 flex items-center pr-2">
                        <x-heroicon name="o-chevron-down" class="h-4 w-4 text-gray-400" />
                    </button>
                </div>
            </div>
        </x-slot>
        <x-slot:content>
            @foreach (auth()->user()->projects as $project)
                <x-dropdown-link class="relative" :href="route('settings.projects.switch', ['project' => $project])">
                    <span class="block truncate">{{ $project->name }}</span>
                    @if ($project->id == auth()->user()->current_project_id)
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-primary-600">
                            <x-heroicon name="o-check" class="h-5 w-5" />
                        </span>
                    @endif
                </x-dropdown-link>
            @endforeach

            @if (auth()->user()->isAdmin())
                <x-dropdown-link href="{{ route('settings.projects') }}">
                    {{ __("Projects List") }}
                </x-dropdown-link>
                <x-dropdown-link href="{{ route('settings.projects', ['create' => 'open']) }}">
                    {{ __("Create a Project") }}
                </x-dropdown-link>
            @endif
        </x-slot>
    </x-dropdown>
</div>
