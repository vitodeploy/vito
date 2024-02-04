<div>
    <x-card-header>
        <x-slot name="title">{{ __("Projects") }}</x-slot>
        <x-slot name="description">{{ __("Here you can manage your projects") }}</x-slot>
        <x-slot name="aside">
            <livewire:projects.create-project />
        </x-slot>
    </x-card-header>
    <div x-data="" class="space-y-3">
        @foreach($projects as $project)
            <x-item-card>
                <div class="ml-3 flex flex-grow flex-col items-start justify-center">
                    <div class="mb-1 flex items-center">
                        {{ $project->name }}
                        @if($project->id == auth()->user()->current_project_id)
                            <x-status status="success" class="ml-1">{{ __('Current') }}</x-status>
                        @endif
                    </div>
                    <span class="text-sm text-gray-400">
                        <x-datetime :value="$project->created_at" />
                    </span>
                </div>
                <div class="flex items-center">
                    <livewire:projects.edit-project :project="$project" />
                    <x-icon-button x-on:click="$wire.deleteId = '{{ $project->id }}'; $dispatch('open-modal', 'delete-project')">
                        Delete
                    </x-icon-button>
                </div>
            </x-item-card>
        @endforeach
        <x-confirm-modal
            name="delete-project"
            :title="__('Confirm')"
            :description="__('Deleting a project will delete all of its servers, sites, etc. Are you sure you want to delete this project?')"
            method="delete"
        />
    </div>
</div>
