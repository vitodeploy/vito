<?php

namespace App\Web\Pages\Settings\SSHKeys\Widgets;

use App\Models\SshKey;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class SshKeysList extends TableWidget
{
    protected $listeners = ['$refresh'];

    protected function getTableQuery(): Builder
    {
        return SshKey::query()->where('user_id', auth()->id());
    }

    protected static ?string $heading = '';

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('name')
                ->sortable()
                ->searchable(),
            TextColumn::make('public_key')
                ->tooltip('Copy')
                ->limit(20)
                ->copyable(),
            TextColumn::make('created_at')
                ->sortable(),
        ];
    }

    public function getTable(): Table
    {
        return $this->table
            ->actions([
                DeleteAction::make('delete')
                    ->requiresConfirmation()
                    ->authorize(fn (SshKey $record) => auth()->user()->can('delete', $record))
                    ->action(function (SshKey $record) {
                        run_action($this, function () use ($record) {
                            $record->delete();
                            $this->dispatch('$refresh');
                        });
                    }),
            ]);
    }
}
