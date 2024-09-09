<?php

namespace App\Filament\Resources\ProjectResource\Widgets;

use App\Models\Project;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as Widget;
use Illuminate\Database\Eloquent\Builder;

class ProjectUsersList extends Widget
{
    protected $listeners = ['userAdded' => '$refresh'];

    public Project $project;

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    protected function getTableQuery(): Builder
    {
        return $this->project->users()->getQuery();
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('id')->width('20%'),
            Tables\Columns\TextColumn::make('name')->width('20%'),
            Tables\Columns\TextColumn::make('email')->width('20%'),
        ];
    }

    public function getTable(): Table
    {
        return $this->table->actions([
            Tables\Actions\DeleteAction::make()->label('Remove')
                ->visible(function ($record) {
                    return $this->authorize('update', $this->project)->allowed() && $record->id !== auth()->id();
                }),
        ])->paginated(false);
    }
}
