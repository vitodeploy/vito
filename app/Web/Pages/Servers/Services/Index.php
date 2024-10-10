<?php

namespace App\Web\Pages\Servers\Services;

use App\Actions\Service\Install;
use App\Models\Service;
use App\Web\Pages\Servers\Page;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;

class Index extends Page
{
    protected static ?string $slug = 'servers/{server}/services';

    protected static ?string $title = 'Services';

    public function mount(): void
    {
        $this->authorize('viewAny', [Service::class, $this->server]);
    }

    public function getWidgets(): array
    {
        return [
            [Widgets\ServicesList::class, ['server' => $this->server]],
        ];
    }

    protected function getHeaderActions(): array
    {
        $availableServices = [];
        foreach (config('core.service_handlers') as $key => $addOn) {
            if (! $this->server->services()->where('name', $key)->exists()) {
                $availableServices[$key] = $key;
            }
        }

        return [
            Action::make('install')
                ->label('Install Service')
                ->icon('heroicon-o-archive-box-arrow-down')
                ->modalWidth(MaxWidth::Large)
                ->authorize(fn () => auth()->user()?->can('create', [Service::class, $this->server]))
                ->modalSubmitActionLabel('Install')
                ->form([
                    Select::make('name')
                        ->searchable()
                        ->options($availableServices)
                        ->reactive()
                        ->rules(fn ($get) => Install::rules($get())['name']),
                    Select::make('version')
                        ->options(function (callable $get) {
                            if (! $get('name')) {
                                return [];
                            }

                            return collect(config("core.service_versions.{$get('name')}"))
                                ->mapWithKeys(fn ($version) => [$version => $version]);
                        })
                        ->rules(fn ($get) => Install::rules($get())['version'])
                        ->reactive(),
                ])
                ->action(function (array $data) {
                    $this->validate();

                    try {
                        app(Install::class)->install($this->server, $data);

                        $this->redirect(self::getUrl(['server' => $this->server]));
                    } catch (Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title($e->getMessage())
                            ->send();

                        throw $e;
                    }

                    $this->dispatch('$refresh');
                }),
        ];
    }
}
