<?php

namespace App\Web\Pages\Servers\Services\Widgets;

use App\Actions\Service\Manage;
use App\Actions\Service\Uninstall;
use App\Models\Server;
use App\Models\Service;
use App\Web\Pages\Servers\Services\Index;
use Exception;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class ServicesList extends TableWidget
{
    public Server $server;

    protected $listeners = ['$refresh'];

    protected function getTableQuery(): Builder
    {
        return Service::query()->where('server_id', $this->server->id);
    }

    protected function getTableColumns(): array
    {
        return [
            IconColumn::make('id')
                ->label('Service')
                ->icon(fn (Service $record) => 'icon-'.$record->name)
                ->width(24),
            TextColumn::make('name')
                ->sortable(),
            TextColumn::make('version')
                ->sortable(),
            TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->color(fn (Service $service) => Service::$statusColors[$service->status])
                ->sortable(),
            TextColumn::make('created_at')
                ->label('Installed At')
                ->formatStateUsing(fn ($record) => $record->created_at_by_timezone)
                ->sortable(),
        ];
    }

    /**
     * @throws Exception
     */
    public function table(Table $table): Table
    {
        return $table
            ->heading('Installed Services')
            ->query($this->getTableQuery())
            ->columns($this->getTableColumns())
            ->actions([
                ActionGroup::make([
                    $this->serviceAction('start', 'heroicon-o-play'),
                    $this->serviceAction('stop', 'heroicon-o-stop'),
                    $this->serviceAction('restart', 'heroicon-o-arrow-path'),
                    $this->serviceAction('disable', 'heroicon-o-x-mark'),
                    $this->serviceAction('enable', 'heroicon-o-check'),
                    $this->uninstallAction(),
                ]),
            ]);
    }

    private function serviceAction(string $type, string $icon): Action
    {
        return Action::make($type)
            ->authorize(fn (Service $service) => auth()->user()?->can($type, $service))
            ->label(ucfirst($type).' Service')
            ->icon($icon)
            ->action(function (Service $service) use ($type) {
                try {
                    app(Manage::class)->$type($service);
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
            ->authorize(fn (Service $service) => auth()->user()?->can('delete', $service))
            ->label('Uninstall Service')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->action(function (Service $service) {
                try {
                    app(Uninstall::class)->uninstall($service);

                    $this->redirect(Index::getUrl(['server' => $this->server]));
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
