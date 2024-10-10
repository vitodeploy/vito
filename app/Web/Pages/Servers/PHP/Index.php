<?php

namespace App\Web\Pages\Servers\PHP;

use App\Actions\PHP\InstallNewPHP;
use App\Models\Service;
use App\Web\Pages\Servers\Page;
use App\Web\Pages\Servers\PHP\Widgets\PHPList;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;

class Index extends Page
{
    protected static ?string $slug = 'servers/{server}/php';

    protected static ?string $title = 'PHP';

    public function mount(): void
    {
        $this->authorize('viewAny', [Service::class, $this->server]);
    }

    public function getWidgets(): array
    {
        return [
            [PHPList::class, ['server' => $this->server]],
        ];
    }

    protected function getHeaderActions(): array
    {
        $installedPHPs = $this->server->installedPHPVersions();

        return [
            Action::make('install')
                ->authorize(fn () => auth()->user()?->can('create', [Service::class, $this->server]))
                ->label('Install PHP')
                ->icon('heroicon-o-archive-box-arrow-down')
                ->modalWidth(MaxWidth::Large)
                ->form([
                    Select::make('version')
                        ->options(
                            collect(config('core.php_versions'))
                                ->filter(fn ($version) => ! in_array($version, $installedPHPs))
                                ->mapWithKeys(fn ($version) => [$version => $version])
                                ->toArray()
                        )
                        ->rules(InstallNewPHP::rules($this->server)['version']),
                ])
                ->modalSubmitActionLabel('Install')
                ->action(function (array $data) {
                    app(InstallNewPHP::class)->install($this->server, $data);

                    Notification::make()
                        ->success()
                        ->title('Installing PHP...')
                        ->send();

                    $this->dispatch('$refresh');
                }),
        ];
    }
}
