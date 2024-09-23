<x-filament-panels::page>
    @livewire(\App\Web\Resources\Project\Widgets\UpdateProject::class, ["project" => $record])

    @livewire(\App\Web\Resources\Project\Widgets\AddUser::class, ["project" => $record])

    @livewire(\App\Web\Resources\Project\Widgets\ProjectUsersList::class, ["project" => $record])
</x-filament-panels::page>
