<?php

namespace App\Web\Pages\Servers\Firewall\Widgets;

use App\Actions\FirewallRule\DeleteRule;
use App\Models\FirewallRule;
use App\Models\Server;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as Widget;
use Illuminate\Database\Eloquent\Builder;

class RulesList extends Widget
{
    public Server $server;

    protected $listeners = ['$refresh'];

    protected function getTableQuery(): Builder
    {
        return FirewallRule::query()->where('server_id', $this->server->id);
    }

    protected static ?string $heading = '';

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('type')
                ->sortable()
                ->extraAttributes(['class' => 'uppercase'])
                ->color(fn (FirewallRule $record) => $record->type === 'allow' ? 'green' : 'red'),
            TextColumn::make('protocol')
                ->sortable()
                ->extraAttributes(['class' => 'uppercase']),
            TextColumn::make('port')
                ->sortable(),
            TextColumn::make('source')
                ->sortable(),
            TextColumn::make('mask')
                ->sortable(),
        ];
    }

    public function getTable(): Table
    {
        return $this->table
            ->actions([
                Action::make('delete')
                    ->icon('heroicon-o-trash')
                    ->tooltip('Delete')
                    ->color('danger')
                    ->hiddenLabel()
                    ->requiresConfirmation()
                    ->authorize(fn (FirewallRule $record) => auth()->user()->can('delete', $record))
                    ->action(function (FirewallRule $record) {
                        try {
                            app(DeleteRule::class)->delete($this->server, $record);
                        } catch (\Exception $e) {
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
