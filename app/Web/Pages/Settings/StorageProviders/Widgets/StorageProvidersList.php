<?php

namespace App\Web\Pages\Settings\StorageProviders\Widgets;

use App\Actions\StorageProvider\DeleteStorageProvider;
use App\Models\StorageProvider;
use App\Web\Pages\Settings\StorageProviders\Actions\Edit;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as Widget;
use Illuminate\Database\Eloquent\Builder;

class StorageProvidersList extends Widget
{
    protected function getTableQuery(): Builder
    {
        return StorageProvider::getByProjectId(auth()->user()->current_project_id);
    }

    protected static ?string $heading = '';

    protected function getTableColumns(): array
    {
        return [
            ImageColumn::make('image_url')
                ->label('Provider')
                ->size(24),
            TextColumn::make('name')
                ->default(fn ($record) => $record->profile)
                ->label('Name')
                ->searchable()
                ->sortable(),
            TextColumn::make('id')
                ->label('Global')
                ->badge()
                ->color(fn ($record) => $record->project_id ? 'gray' : 'success')
                ->formatStateUsing(function (StorageProvider $record) {
                    return $record->project_id ? 'No' : 'Yes';
                }),
            TextColumn::make('created_at')
                ->label('Created At')
                ->formatStateUsing(fn ($record) => $record->created_at_by_timezone)
                ->searchable()
                ->sortable(),
        ];
    }

    public function getTable(): Table
    {
        return $this->table->actions([
            EditAction::make('edit')
                ->label('Edit')
                ->modalHeading('Edit Storage Provider')
                ->mutateRecordDataUsing(function (array $data, StorageProvider $record) {
                    return [
                        'name' => $record->profile,
                        'global' => $record->project_id === null,
                    ];
                })
                ->form(Edit::form())
                ->authorize(fn (StorageProvider $record) => auth()->user()->can('update', $record))
                ->using(fn (array $data, StorageProvider $record) => Edit::action($record, $data))
                ->modalWidth(MaxWidth::Medium),
            DeleteAction::make('delete')
                ->label('Delete')
                ->modalHeading('Delete Storage Provider')
                ->authorize(fn (StorageProvider $record) => auth()->user()->can('delete', $record))
                ->using(function (array $data, StorageProvider $record) {
                    app(DeleteStorageProvider::class)->delete($record);
                }),
        ]);
    }
}
