<?php

namespace App\Web\Pages\Servers\Databases\Widgets;

use App\Actions\Database\DeleteDatabase;
use App\Models\Database;
use App\Models\Server;
use App\Models\User;
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
                        });
                    }),
            ]);
    }
}
