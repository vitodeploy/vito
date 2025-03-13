<?php

namespace App\Web\Pages\Settings\SSHKeys\Widgets;

use App\Models\SshKey;
use App\Models\User;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class SshKeysList extends TableWidget
{
    /**
     * @var array<string>
     */
    protected $listeners = ['$refresh'];

    /**
     * @return Builder<SshKey>
     */
    protected function getTableQuery(): Builder
    {
        return SshKey::query()->where('user_id', auth()->id());
    }

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
                    ->requiresConfirmation()
                    ->authorize(fn (SshKey $record) => $user->can('delete', $record))
                    ->action(function (SshKey $record): void {
                        run_action($this, function () use ($record): void {
                            $record->delete();
                            $this->dispatch('$refresh');
                        });
                    }),
            ]);
    }
}
