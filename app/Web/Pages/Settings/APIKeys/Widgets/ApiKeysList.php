<?php

namespace App\Web\Pages\Settings\APIKeys\Widgets;

use App\Models\PersonalAccessToken;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as Widget;
use Illuminate\Database\Eloquent\Builder;

class ApiKeysList extends Widget
{
    protected $listeners = ['$refresh'];

    protected function getTableQuery(): Builder
    {
        return auth()->user()->tokens()->getQuery();
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('name')
                ->searchable()
                ->sortable(),
            TextColumn::make('abilities')
                ->searchable()
                ->sortable(),
            TextColumn::make('created_at')
                ->label('Created At')
                ->formatStateUsing(fn (PersonalAccessToken $record) => $record->created_at_by_timezone)
                ->searchable()
                ->sortable(),
            TextColumn::make('last_used_at')
                ->label('Last Used At')
                ->formatStateUsing(fn (PersonalAccessToken $record) => $record->getDateTimeByTimezone($record->last_used_at))
                ->searchable()
                ->sortable(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(null)
            ->query($this->getTableQuery())
            ->columns($this->getTableColumns())
            ->actions([
                DeleteAction::make('delete')
                    ->modalHeading('Delete Token')
                    ->authorize(fn (PersonalAccessToken $record) => auth()->user()->can('delete', $record))
                    ->using(function (array $data, PersonalAccessToken $record) {
                        $record->delete();
                    }),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->requiresConfirmation()
                    ->authorize(auth()->user()->can('deleteMany', PersonalAccessToken::class)),
            ]);
    }
}
