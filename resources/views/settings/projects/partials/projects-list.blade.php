<div>
    <x-card-header>
        <x-slot name="title">{{ __("Projects") }}</x-slot>
        <x-slot name="description">
            {{ __("Here you can manage your projects") }}
        </x-slot>
        <x-slot name="aside">
            @include("settings.projects.partials.create-project")
        </x-slot>
    </x-card-header>
    <div id="projects-list" x-data="{ deleteAction: '' }" class="space-y-3">
        @foreach ($projects as $project)
            <x-item-card>
                <div class="ml-3 flex flex-grow flex-col items-start justify-center">
                    <div class="mb-1 flex items-center">
                        {{ $project->name }}
                        @if ($project->id == auth()->user()->current_project_id)
                            <x-status status="success" class="ml-1">
                                {{ __("Current") }}
                            </x-status>
                        @endif
                    </div>
                    <span class="text-sm text-gray-400">
                        <x-datetime :value="$project->created_at" />
                    </span>
                </div>
                <div class="flex items-center">
                    @include("settings.projects.partials.edit-project", ["project" => $project])
                    <x-icon-button
                        x-on:click="deleteAction = '{{ route('settings.projects.delete', $project) }}'; $dispatch('open-modal', 'delete-project')"
                    >
                        <x-heroicon name="o-trash" class="h-5 w-5" />
                    </x-icon-button>
                </div>
            </x-item-card>
        @endforeach

        @include("settings.projects.partials.delete-project")
    </div>
</div>
