<x-filament-panels::page>
    @livewire(\App\Filament\Resources\ProjectResource\Widgets\UpdateProject::class, ["project" => $record])

    @livewire(\App\Filament\Resources\ProjectResource\Widgets\AddUser::class, ["project" => $record])

    @livewire(\App\Filament\Resources\ProjectResource\Widgets\ProjectUsersList::class, ["project" => $record])
</x-filament-panels::page>
