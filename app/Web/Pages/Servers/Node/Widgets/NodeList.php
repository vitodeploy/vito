<?php

namespace App\Web\Pages\Servers\Node\Widgets;

use App\Actions\Node\ChangeDefaultCli;
use App\Actions\Service\Uninstall;
use App\Models\Server;
use App\Models\Service;
use Exception;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as Widget;
use Illuminate\Database\Eloquent\Builder;

class NodeList extends Widget
{
    public Server $server;

    protected $listeners = ['$refresh'];

    protected function getTableQuery(): Builder
    {
        return Service::query()->where('type', 'node')->where('server_id', $this->server->id);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('version')
                ->sortable(),
            TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->color(fn (Service $service) => Service::$statusColors[$service->status])
                ->sortable(),
            TextColumn::make('is_default')
                ->label('Default Cli')
                ->badge()
                ->color(fn (Service $service) => $service->is_default ? 'primary' : 'gray')
                ->state(fn (Service $service) => $service->is_default ? 'Yes' : 'No')
                ->sortable(),
            TextColumn::make('created_at')
                ->label('Installed At')
                ->formatStateUsing(fn ($record) => $record->created_at_by_timezone),
        ];
    }

    /**
     * @throws Exception
     */
    public function table(Table $table): Table
    {
        return $table
            ->heading(null)
            ->query($this->getTableQuery())
            ->columns($this->getTableColumns())
            ->actions([
                ActionGroup::make([
                    $this->defaultNodeCliAction(),
                    $this->uninstallAction(),
                ]),
            ]);
    }

    private function defaultNodeCliAction(): Action
    {
        return Action::make('default-node-cli')
            ->authorize(fn (Service $node) => auth()->user()?->can('update', $node))
            ->label('Make Default CLI')
            ->hidden(fn (Service $service) => $service->is_default)
            ->action(function (Service $service) {
                try {
                    app(ChangeDefaultCli::class)->change($this->server, ['version' => $service->version]);

                    Notification::make()
                        ->success()
                        ->title('Default Node CLI changed!')
                        ->send();
                } catch (Exception $e) {
                    Notification::make()
                        ->danger()
                        ->title($e->getMessage())
                        ->send();

                    throw $e;
                }

                $this->dispatch('$refresh');
            });
    }

    private function uninstallAction(): Action
    {
        return Action::make('uninstall')
            ->authorize(fn (Service $node) => auth()->user()?->can('update', $node))
            ->label('Uninstall')
            ->color('danger')
            ->requiresConfirmation()
            ->action(function (Service $service) {
                try {
                    app(Uninstall::class)->uninstall($service);
                } catch (Exception $e) {
                    Notification::make()
                        ->danger()
                        ->title($e->getMessage())
                        ->send();

                    throw $e;
                }

                $this->dispatch('$refresh');
            });
    }
}
