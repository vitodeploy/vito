<?php

namespace App\Web\Pages\Servers\PHP;

use App\Actions\PHP\InstallNewPHP;
use App\Models\Service;
use App\Web\Pages\Servers\Page;
use App\Web\Pages\Servers\PHP\Widgets\PHPList;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Support\Enums\IconPosition;

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
        $phps = [];
        foreach (config('core.php_versions') as $version) {
            if (! $this->server->service('php', $version) && $version !== 'none') {
                $phps[] = Action::make($version)
                    ->label($version)
                    ->requiresConfirmation()
                    ->modalHeading('Install PHP '.$version)
                    ->modalSubmitActionLabel('Install')
                    ->action(function () use ($version) {
                        app(InstallNewPHP::class)->install($this->server, ['version' => $version]);

                        $this->dispatch('$refresh');
                    });
            }
        }

        return [
            ActionGroup::make($phps)
                ->authorize(fn () => auth()->user()?->can('create', [Service::class, $this->server]))
                ->label('Install PHP')
                ->icon('heroicon-o-chevron-up-down')
                ->iconPosition(IconPosition::After)
                ->dropdownPlacement('bottom-end')
                ->color('primary')
                ->button(),
        ];
    }
}
