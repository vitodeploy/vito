<?php

namespace App\Web\Pages\Settings\APIKeys\Widgets;

use App\Models\PersonalAccessToken;
use App\Models\User;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as Widget;
use Illuminate\Database\Eloquent\Builder;

class ApiKeysList extends Widget
{
    /**
     * @var array<string>
     */
    protected $listeners = ['$refresh'];

    /**
     * @return Builder<PersonalAccessToken>
     */
    protected function getTableQuery(): Builder
    {
        /** @var User $user */
        $user = auth()->user();

        /** @var Builder<PersonalAccessToken> $query */
        $query = $user->tokens()->getQuery();

        return $query;
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
                ->formatStateUsing(fn (PersonalAccessToken $record): string => $record->getDateTimeByTimezone($record->last_used_at))
                ->searchable()
                ->sortable(),
        ];
    }

    public function table(Table $table): Table
    {
        /** @var User $user */
        $user = auth()->user();

        return $table
            ->heading(null)
            ->query($this->getTableQuery())
            ->columns($this->getTableColumns())
            ->actions([
                DeleteAction::make('delete')
                    ->modalHeading('Delete Token')
                    ->authorize(fn (PersonalAccessToken $record) => $user->can('delete', $record))
                    ->using(function (array $data, PersonalAccessToken $record): void {
                        $record->delete();
                    }),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->requiresConfirmation()
                    ->authorize($user->can('deleteMany', PersonalAccessToken::class)),
            ]);
    }
}
