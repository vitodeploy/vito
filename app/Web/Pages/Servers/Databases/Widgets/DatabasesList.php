<?php

namespace App\Web\Pages\Servers\Databases\Widgets;

use App\Actions\Database\DeleteDatabase;
use App\Actions\Database\DuplicateDatabase;
use App\Models\Database;
use App\Models\Server;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as Widget;
use Illuminate\Database\Eloquent\Builder;

class DatabasesList extends Widget
{
    public Server $server;

    /**
     * @var array<string>
     */
    protected $listeners = ['$refresh'];

    /**
     * @return Builder<Database>
     */
    protected function getTableQuery(): Builder
    {
        return Database::query()->where('server_id', $this->server->id);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('name')
                ->searchable(),
            TextColumn::make('charset')
                ->label('Charset / Encoding')
                ->sortable(),
            TextColumn::make('collation')
                ->label('Collation')
                ->sortable(),
            TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->color(fn (Database $database) => Database::$statusColors[$database->status])
                ->sortable(),
            TextColumn::make('created_at')
                ->label('Created At')
                ->formatStateUsing(fn ($record) => $record->created_at_by_timezone)
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
                Action::make('duplicate')
                    ->hiddenLabel()
                    ->icon('heroicon-o-square-2-stack')
                    ->modalHeading('Duplicate Database')
                    ->tooltip('Duplicate')
                    ->authorize(fn ($record) => $user->can('create', [Database::class, $this->server]))
                    ->form([
                        \Filament\Forms\Components\TextInput::make('name')
                            ->label('New Database Name')
                            ->required()
                            ->helperText('The name for the duplicated database')
                            ->rules(fn (Database $record) => DuplicateDatabase::rules($record)['name']),
                    ])
                    ->action(function (Database $record, array $data): void {
                        run_action($this, function () use ($record, $data): void {
                            app(DuplicateDatabase::class)->duplicate($record, $data);
                            $this->dispatch('$refresh');
                            Notification::make()
                                ->success()
                                ->title('Databases duplicated!')
                                ->send();
                        });
                    }),
                Action::make('delete')
                    ->hiddenLabel()
                    ->icon('heroicon-o-trash')
                    ->modalHeading('Delete Database')
                    ->color('danger')
                    ->tooltip('Delete')
                    ->authorize(fn ($record) => $user->can('delete', $record))
                    ->requiresConfirmation()
                    ->action(function (Database $record): void {
                        run_action($this, function () use ($record): void {
                            app(DeleteDatabase::class)->delete($this->server, $record);
                            $this->dispatch('$refresh');
                            Notification::make()
                                ->success()
                                ->title('Databases deleted!')
                                ->send();
                        });
                    }),
            ]);
    }
}
