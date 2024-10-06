<?php

namespace App\Web\Pages\Servers\SSHKeys\Widgets;

use App\Actions\SshKey\DeleteKeyFromServer;
use App\Models\Server;
use App\Models\SshKey;
use Exception;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class SshKeysList extends TableWidget
{
    public Server $server;

    protected $listeners = ['$refresh'];

    protected function getTableQuery(): Builder
    {
        return SshKey::withTrashed()
            ->whereHas(
                'servers',
                fn (Builder $query) => $query->where('server_id', $this->server->id)
            );
    }

    protected static ?string $heading = '';

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('name')
                ->sortable()
                ->searchable(),
            TextColumn::make('user.name')
                ->sortable()
                ->searchable(),
            TextColumn::make('created_at')
                ->sortable(),
        ];
    }

    public function getTable(): Table
    {
        return $this->table
            ->actions([
                DeleteAction::make('delete')
                    ->hiddenLabel()
                    ->authorize(fn (SshKey $record) => auth()->user()->can('deleteServer', [SshKey::class, $this->server]))
                    ->using(function (SshKey $record) {
                        try {
                            app(DeleteKeyFromServer::class)->delete($this->server, $record);
                        } catch (Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title($e->getMessage())
                                ->send();
                        }

                        $this->dispatch('$refresh');
                    }),
            ]);
    }
}
