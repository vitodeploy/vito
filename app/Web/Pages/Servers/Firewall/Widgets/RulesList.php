<?php

namespace App\Web\Pages\Servers\Firewall\Widgets;

use App\Actions\FirewallRule\ManageRule;
use App\Models\FirewallRule;
use App\Models\Server;
use App\Web\Pages\Servers\Firewall\Index;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class RulesList extends Widget
{
    public Server $server;

    protected $listeners = ['$refresh'];

    protected function getTableQuery(): Builder
    {
        return FirewallRule::query()->where('server_id', $this->server->id);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('name')
                ->searchable()
                ->sortable()
                ->label('Purpose'),
            TextColumn::make('type')
                ->sortable()
                ->badge()
                ->color(fn ($state) => $state === 'allow' ? 'success' : 'warning')
                ->label('Type')
                ->formatStateUsing(fn ($state) => Str::upper($state)),
            TextColumn::make('id')
                ->sortable()
                ->label('Source')
                ->formatStateUsing(function (FirewallRule $record) {
                    $source = $record->source == null ? 'any' : $record->source;
                    if ($source !== 'any' && $record->mask !== null) {
                        $source .= '/'.$record->mask;
                    }

                    return $source;
                }),
            TextColumn::make('protocol')
                ->sortable()
                ->badge()
                ->color('primary')
                ->label('Protocol')
                ->formatStateUsing(fn ($state) => Str::upper($state)),
            TextColumn::make('port')
                ->sortable()
                ->label('Port'),
            TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->color(fn (FirewallRule $record) => $record->getStatusColor()),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(null)
            ->query($this->getTableQuery())
            ->columns($this->getTableColumns())
            ->actions([
                Action::make('edit')
                    ->icon('heroicon-o-pencil')
                    ->tooltip('Edit')
                    ->hiddenLabel()
                    ->modalWidth(MaxWidth::Large)
                    ->modalHeading('Edit Firewall Rule')
                    ->modalDescription('Edit the associated servers firewall rule.')
                    ->modalSubmitActionLabel('Update')
                    ->authorize(fn (FirewallRule $record) => auth()->user()->can('update', $record))
                    ->form(fn ($record) => Index::getFirewallForm($record))
                    ->action(function (FirewallRule $record, array $data) {
                        run_action($this, function () use ($record, $data) {
                            app(ManageRule::class)->update($record, $data);

                            $this->dispatch('$refresh');

                            Notification::make()
                                ->success()
                                ->title('Applying Firewall Rule')
                                ->send();
                        });
                    }),
                Action::make('delete')
                    ->icon('heroicon-o-trash')
                    ->tooltip('Delete')
                    ->color('danger')
                    ->hiddenLabel()
                    ->requiresConfirmation()
                    ->authorize(fn (FirewallRule $record) => auth()->user()->can('delete', $record))
                    ->action(function (FirewallRule $record) {
                        try {
                            app(ManageRule::class)->delete($record);
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
