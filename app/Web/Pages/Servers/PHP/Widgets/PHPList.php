<?php

namespace App\Web\Pages\Servers\PHP\Widgets;

use App\Actions\PHP\ChangeDefaultCli;
use App\Actions\PHP\GetPHPIni;
use App\Actions\PHP\InstallPHPExtension;
use App\Actions\PHP\UpdatePHPIni;
use App\Actions\Service\Manage;
use App\Actions\Service\Uninstall;
use App\Enums\PHPIniType;
use App\Models\Server;
use App\Models\Service;
use App\Web\Pages\Servers\Logs\Index;
use Exception;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as Widget;
use Illuminate\Database\Eloquent\Builder;

class PHPList extends Widget
{
    public Server $server;

    protected $listeners = ['$refresh'];

    protected function getTableQuery(): Builder
    {
        return Service::query()->where('type', 'php')->where('server_id', $this->server->id);
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
                    $this->installExtensionAction(),
                    $this->editPHPIniAction(PHPIniType::CLI),
                    $this->editPHPIniAction(PHPIniType::FPM),
                    $this->defaultPHPCliAction(),
                    $this->restartFPMAction(),
                    $this->uninstallAction(),
                ]),
            ]);
    }

    private function installExtensionAction(): Action
    {
        return Action::make('install-extension')
            ->authorize(fn (Service $php) => auth()->user()?->can('update', $php))
            ->label('Install Extension')
            ->modalHeading('Install PHP Extension')
            ->modalWidth(MaxWidth::Large)
            ->modalSubmitActionLabel('Install')
            ->form([
                Hidden::make('version')
                    ->default(fn (Service $service) => $service->version)
                    ->rules(InstallPHPExtension::rules($this->server)['version']),
                Select::make('extension')
                    ->options(
                        collect(config('core.php_extensions'))
                            ->mapWithKeys(fn ($extension) => [$extension => $extension])
                    )
                    ->rules(InstallPHPExtension::rules($this->server)['extension']),
            ])
            ->action(function (array $data) {
                $this->validate();

                try {
                    app(InstallPHPExtension::class)->install($this->server, $data);

                    Notification::make()
                        ->success()
                        ->title('PHP Extension is being installed!')
                        ->body('You can check the logs for more information.')
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('View Logs')
                                ->url(Index::getUrl(parameters: ['server' => $this->server->id])),
                        ])
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

    private function defaultPHPCliAction(): Action
    {
        return Action::make('default-php-cli')
            ->authorize(fn (Service $php) => auth()->user()?->can('update', $php))
            ->label('Make Default CLI')
            ->hidden(fn (Service $service) => $service->is_default)
            ->action(function (Service $service) {
                try {
                    app(ChangeDefaultCli::class)->change($this->server, ['version' => $service->version]);

                    Notification::make()
                        ->success()
                        ->title('Default PHP CLI changed!')
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

    private function editPHPIniAction(string $type): Action
    {
        return Action::make('php-ini-'.$type)
            ->authorize(fn (Service $php) => auth()->user()?->can('update', $php))
            ->label('Update PHP ini ('.$type.')')
            ->modalWidth(MaxWidth::TwoExtraLarge)
            ->modalSubmitActionLabel('Save')
            ->form([
                Hidden::make('type')
                    ->default($type)
                    ->rules(UpdatePHPIni::rules($this->server)['type']),
                Hidden::make('version')
                    ->default(fn (Service $service) => $service->version)
                    ->rules(UpdatePHPIni::rules($this->server)['version']),
                Textarea::make('ini')
                    ->label('PHP ini')
                    ->rows(20)
                    ->rules(UpdatePHPIni::rules($this->server)['ini'])
                    ->default(fn (Service $service) => app(GetPHPIni::class)->getIni($this->server, [
                        'type' => $type,
                        'version' => $service->version,
                    ])),
            ])
            ->action(function (array $data) {
                $this->validate();

                try {
                    app(UpdatePHPIni::class)->update($this->server, $data);

                    Notification::make()
                        ->success()
                        ->title('PHP ini updated!')
                        ->body('Restarting PHP...')
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

    private function restartFPMAction(): Action
    {
        return Action::make('restart-fpm')
            ->authorize(fn (Service $php) => auth()->user()?->can('update', $php))
            ->label('Restart PHP FPM')
            ->action(function (Service $service) {
                try {
                    app(Manage::class)->restart($service);
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
            ->authorize(fn (Service $php) => auth()->user()?->can('update', $php))
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
