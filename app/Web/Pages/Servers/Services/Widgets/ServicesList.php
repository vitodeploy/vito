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
use Filament\Tables\Columns\ImageColumn;
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

    protected static ?string $heading = 'Installed Services';

    protected function getTableColumns(): array
    {
        return [
            ImageColumn::make('image_url')
                ->label('Service')
                ->size(24),
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
                ->formatStateUsing(fn ($record) => $record->created_at_by_timezone),
        ];
    }

    /**
     * @throws Exception
     */
    public function getTable(): Table
    {
        return $this->table
            ->actions([
                ActionGroup::make([
                    $this->serviceAction('start'),
                    $this->serviceAction('stop'),
                    $this->serviceAction('restart'),
                    $this->serviceAction('disable'),
                    $this->serviceAction('enable'),
                    $this->uninstallAction(),
                ]),
            ]);
    }

    private function serviceAction(string $type): Action
    {
        return Action::make($type)
            ->authorize(fn (Service $service) => auth()->user()?->can($type, $service))
            ->label(ucfirst($type).' Service')
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
