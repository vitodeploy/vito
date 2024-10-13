<?php

namespace App\Web\Pages\Settings\ServerProviders\Widgets;

use App\Actions\ServerProvider\DeleteServerProvider;
use App\Models\ServerProvider;
use App\Web\Pages\Settings\ServerProviders\Actions\Edit;
use Exception;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as Widget;
use Illuminate\Database\Eloquent\Builder;

class ServerProvidersList extends Widget
{
    protected function getTableQuery(): Builder
    {
        return ServerProvider::getByProjectId(auth()->user()->current_project_id);
    }

    protected static ?string $heading = '';

    protected function getTableColumns(): array
    {
        return [
            IconColumn::make('provider')
                ->icon(fn (ServerProvider $record) => 'icon-'.$record->provider)
                ->width(24),
            TextColumn::make('name')
                ->default(fn ($record) => $record->profile)
                ->label('Name')
                ->searchable()
                ->sortable(),
            TextColumn::make('id')
                ->label('Global')
                ->badge()
                ->color(fn ($record) => $record->project_id ? 'gray' : 'success')
                ->formatStateUsing(function (ServerProvider $record) {
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
                ->modalHeading('Edit Server Provider')
                ->mutateRecordDataUsing(function (array $data, ServerProvider $record) {
                    return [
                        'name' => $record->profile,
                        'global' => $record->project_id === null,
                    ];
                })
                ->form(Edit::form())
                ->authorize(fn (ServerProvider $record) => auth()->user()->can('update', $record))
                ->using(fn (array $data, ServerProvider $record) => Edit::action($record, $data))
                ->modalWidth(MaxWidth::Medium),
            DeleteAction::make('delete')
                ->label('Delete')
                ->modalHeading('Delete Server Provider')
                ->authorize(fn (ServerProvider $record) => auth()->user()->can('delete', $record))
                ->using(function (array $data, ServerProvider $record) {
                    try {
                        app(DeleteServerProvider::class)->delete($record);
                    } catch (Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title($e->getMessage())
                            ->send();
                    }
                }),
        ]);
    }
}
