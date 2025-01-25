<?php

namespace App\Web\Pages\Servers\Databases;

use App\Actions\Database\ManageBackup;
use App\Models\Backup;
use App\Models\StorageProvider;
use App\Web\Contracts\HasSecondSubNav;
use App\Web\Pages\Servers\Page;
use App\Web\Pages\Settings\StorageProviders\Actions\Create;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;

class Backups extends Page implements HasSecondSubNav
{
    use Traits\Navigation;

    protected static ?string $slug = 'servers/{server}/databases/backups';

    protected static ?string $title = 'Backups';

    public function mount(): void
    {
        $this->authorize('viewAny', [Backup::class, $this->server]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->icon('heroicon-o-plus')
                ->modalWidth(MaxWidth::Large)
                ->authorize(fn () => auth()->user()?->can('create', [Backup::class, $this->server]))
                ->form([
                    Select::make('database')
                        ->label('Database')
                        ->options($this->server->databases()->pluck('name', 'id')->toArray())
                        ->rules(fn (callable $get) => ManageBackup::rules($this->server, $get())['database'])
                        ->searchable(),
                    Select::make('storage')
                        ->label('Storage')
                        ->options(StorageProvider::getByProjectId($this->server->project_id)->pluck('profile', 'id')->toArray())
                        ->rules(fn (callable $get) => ManageBackup::rules($this->server, $get())['storage'])
                        ->suffixAction(
                            \Filament\Forms\Components\Actions\Action::make('connect')
                                ->form(Create::form())
                                ->modalHeading('Connect to a new storage provider')
                                ->modalSubmitActionLabel('Connect')
                                ->icon('heroicon-o-wifi')
                                ->tooltip('Connect to a new storage provider')
                                ->modalWidth(MaxWidth::Medium)
                                ->authorize(fn () => auth()->user()->can('create', StorageProvider::class))
                                ->action(fn (array $data) => Create::action($data))
                        ),
                    Select::make('interval')
                        ->label('Interval')
                        ->options(config('core.cronjob_intervals'))
                        ->reactive()
                        ->rules(fn (callable $get) => ManageBackup::rules($this->server, $get())['interval']),
                    TextInput::make('custom_interval')
                        ->label('Custom Interval (Cron)')
                        ->rules(fn (callable $get) => ManageBackup::rules($this->server, $get())['custom_interval'])
                        ->visible(fn (callable $get) => $get('interval') === 'custom')
                        ->placeholder('0 * * * *'),
                    TextInput::make('keep')
                        ->label('Backups to Keep')
                        ->rules(fn (callable $get) => ManageBackup::rules($this->server, $get())['keep'])
                        ->helperText('How many backups to keep before deleting the oldest one'),
                ])
                ->modalSubmitActionLabel('Create')
                ->action(function (array $data) {
                    run_action($this, function () use ($data) {
                        app(ManageBackup::class)->create($this->server, $data);

                        $this->dispatch('$refresh');

                        Notification::make()
                            ->success()
                            ->title('Backup created!')
                            ->send();
                    });
                }),
        ];
    }

    public function getWidgets(): array
    {
        return [
            [Widgets\BackupsList::class, ['server' => $this->server]],
        ];
    }
}
