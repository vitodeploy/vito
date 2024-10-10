<?php

namespace App\Web\Pages\Settings\Projects\Widgets;

use App\Models\Project;
use App\Web\Pages\Settings\Projects\Settings;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as Widget;
use Illuminate\Database\Eloquent\Builder;

class ProjectsList extends Widget
{
    protected $listeners = ['$refresh'];

    protected function getTableQuery(): Builder
    {
        return Project::query();
    }

    protected static ?string $heading = '';

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('name')
                ->searchable()
                ->sortable(),
            TextColumn::make('created_at')
                ->label('Created At')
                ->formatStateUsing(fn ($record) => $record->created_at_by_timezone)
                ->searchable()
                ->sortable(),
        ];
    }

    public function getTable(): Table
    {
        return $this->table
            ->recordUrl(fn (Project $record) => Settings::getUrl(['project' => $record]))
            ->actions([
                Action::make('settings')
                    ->label('Settings')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->authorize(fn ($record) => auth()->user()->can('update', $record))
                    ->url(fn (Project $record) => Settings::getUrl(['project' => $record])),
            ]);
    }
}
