<?php

namespace App\Web\Pages\Servers\Node;

use App\Actions\NodeJS\InstallNewNode;
use App\Enums\NodeJS;
use App\Models\Service;
use App\Web\Pages\Servers\Page;
use App\Web\Pages\Servers\Node\Widgets\NodeList;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;

class Index extends Page
{
    protected static ?string $slug = 'servers/{server}/node';

    protected static ?string $title = 'Node';

    public function mount(): void
    {
        $this->authorize('viewAny', [Service::class, $this->server]);
    }

    public function getWidgets(): array
    {
        return [
            [NodeList::class, ['server' => $this->server]],
        ];
    }

    protected function getHeaderActions(): array
    {
        $installedNodeVersions = $this->server->installedNodejsVersions();

        return [
            Action::make('install')
                ->authorize(fn () => auth()->user()?->can('create', [Service::class, $this->server]))
                ->label('Install Node')
                ->icon('heroicon-o-archive-box-arrow-down')
                ->modalWidth(MaxWidth::Large)
                ->form([
                    Select::make('version')
                        ->options(
                            collect(config('core.nodejs_versions'))
                                ->filter(fn ($version) => ! in_array($version, array_merge($installedNodeVersions, [NodeJS::NONE])))
                                ->mapWithKeys(fn ($version) => [$version => $version])
                                ->toArray()
                        )
                        ->rules(InstallNewNode::rules($this->server)['version']),
                ])
                ->modalSubmitActionLabel('Install')
                ->action(function (array $data) {
                    app(InstallNewNode::class)->install($this->server, $data);

                    Notification::make()
                        ->success()
                        ->title('Installing Node...')
                        ->send();

                    $this->dispatch('$refresh');
                }),
        ];
    }
}
