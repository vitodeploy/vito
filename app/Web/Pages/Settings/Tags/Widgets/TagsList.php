<?php

namespace App\Web\Pages\Settings\Tags\Widgets;

use App\Actions\Tag\DeleteTag;
use App\Models\Tag;
use App\Web\Pages\Settings\Tags\Actions\Edit;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as Widget;
use Illuminate\Database\Eloquent\Builder;

class TagsList extends Widget
{
    protected $listeners = ['$refresh'];

    protected function getTableQuery(): Builder
    {
        return Tag::getByProjectId(auth()->user()->current_project_id);
    }

    protected function getTableColumns(): array
    {
        return [
            ColorColumn::make('color'),
            TextColumn::make('name')
                ->searchable()
                ->sortable(),
            TextColumn::make('created_at')
                ->label('Created At')
                ->formatStateUsing(fn (Tag $record) => $record->created_at_by_timezone)
                ->searchable()
                ->sortable(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('')
            ->query($this->getTableQuery())
            ->columns($this->getTableColumns())
            ->actions([
                $this->editAction(),
                $this->deleteAction(),
            ]);
    }

    private function editAction(): Action
    {
        return EditAction::make('edit')
            ->fillForm(function (Tag $record) {
                return [
                    'name' => $record->name,
                    'color' => $record->color,
                    'global' => $record->project_id === null,
                ];
            })
            ->form(Edit::form())
            ->authorize(fn (Tag $record) => auth()->user()->can('update', $record))
            ->using(fn (array $data, Tag $record) => Edit::action($record, $data))
            ->modalWidth(MaxWidth::Medium);
    }

    private function deleteAction(): Action
    {
        return DeleteAction::make('delete')
            ->authorize(fn (Tag $record) => auth()->user()->can('delete', $record))
            ->using(function (Tag $record) {
                app(DeleteTag::class)->delete($record);
            });
    }
}
